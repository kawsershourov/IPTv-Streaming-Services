<?php
declare(strict_types=1);

/**
 * Minimal, dependency-free reader for MaxMind DB (.mmdb) files — enough to look
 * up the country ISO code for an IP from a GeoLite2-Country database.
 *
 * Pure PHP: no maxminddb C extension, no Composer, no geoip2 package. Implements
 * the public MaxMind DB file format spec (search tree + data section decoder).
 * Reads on demand via fseek/fread so it stays light even though geo_guard runs
 * on every request.
 */
class MmdbReader
{
    private const METADATA_MARKER = "\xab\xcd\xefMaxMind.com";
    private const DATA_SEPARATOR  = 16;

    /** @var resource */
    private $handle;
    private int $fileSize;
    private array $meta;
    private int $nodeCount;
    private int $recordSize;
    private int $ipVersion;
    private int $nodeByteSize;
    private int $searchTreeSize;
    private int $dataSectionStart;
    private ?int $ipv4StartNode = null;

    public function __construct(string $path)
    {
        $h = @fopen($path, 'rb');
        if ($h === false) {
            throw new RuntimeException("Cannot open MMDB file: $path");
        }
        $this->handle   = $h;
        $this->fileSize = (int) (@filesize($path) ?: 0);
        $this->readMetadata();
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    public function metadata(): array
    {
        return $this->meta;
    }

    /** Country ISO code (e.g. "BD") for an IP, or null if not found. */
    public function country(string $ip): ?string
    {
        $rec = $this->get($ip);
        if (!is_array($rec)) {
            return null;
        }
        foreach (['country', 'registered_country'] as $key) {
            if (!empty($rec[$key]['iso_code'])) {
                return strtoupper((string) $rec[$key]['iso_code']);
            }
        }
        return null;
    }

    /** Full record (associative array) for an IP, or null. */
    public function get(string $ip): ?array
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }

        $node = 0;
        // IPv4 address in an IPv6 database: descend the 96-bit zero path first.
        if ($this->ipVersion === 6 && strlen($packed) === 4) {
            $node = $this->ipv4StartNode();
        }

        $len = strlen($packed);
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($packed[$i]);
            for ($b = 7; $b >= 0; $b--) {
                if ($node >= $this->nodeCount) {
                    break 2;
                }
                $bit  = ($byte >> $b) & 1;
                $node = $this->readNode($node, $bit);
            }
        }

        if ($node <= $this->nodeCount) {
            return null; // == nodeCount: empty; < nodeCount: ran out of bits
        }

        $offset = $node - $this->nodeCount + $this->searchTreeSize;
        $decoder = new MmdbDecoder($this->handle, $this->dataSectionStart);
        [$value] = $decoder->decode($offset);
        return is_array($value) ? $value : null;
    }

    private function ipv4StartNode(): int
    {
        if ($this->ipv4StartNode !== null) {
            return $this->ipv4StartNode;
        }
        $node = 0;
        for ($i = 0; $i < 96 && $node < $this->nodeCount; $i++) {
            $node = $this->readNode($node, 0);
        }
        return $this->ipv4StartNode = $node;
    }

    private function readNode(int $node, int $index): int
    {
        $base = $node * $this->nodeByteSize;
        if ($this->recordSize === 28) {
            $b = $this->read($base, 7);
            if ($index === 0) {
                return ((ord($b[3]) & 0xF0) << 20) | (ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2]);
            }
            return ((ord($b[3]) & 0x0F) << 24) | (ord($b[4]) << 16) | (ord($b[5]) << 8) | ord($b[6]);
        }
        if ($this->recordSize === 24) {
            $b = $this->read($base + $index * 3, 3);
            return (ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2]);
        }
        // 32-bit records
        $b = $this->read($base + $index * 4, 4);
        return (ord($b[0]) << 24) | (ord($b[1]) << 16) | (ord($b[2]) << 8) | ord($b[3]);
    }

    private function readMetadata(): void
    {
        $maxScan = min($this->fileSize, 128 * 1024);
        $start   = $this->fileSize - $maxScan;
        $tail    = $this->read($start, $maxScan);
        $pos     = strrpos($tail, self::METADATA_MARKER);
        if ($pos === false) {
            throw new RuntimeException('Invalid MMDB: metadata marker not found');
        }
        $metaOffset = $start + $pos + strlen(self::METADATA_MARKER);

        $decoder = new MmdbDecoder($this->handle, $metaOffset);
        [$meta]  = $decoder->decode($metaOffset);
        if (!is_array($meta) || empty($meta['node_count']) || empty($meta['record_size'])) {
            throw new RuntimeException('Invalid MMDB metadata');
        }

        $this->meta             = $meta;
        $this->nodeCount        = (int) $meta['node_count'];
        $this->recordSize       = (int) $meta['record_size'];
        $this->ipVersion        = (int) ($meta['ip_version'] ?? 6);
        $this->nodeByteSize     = intdiv($this->recordSize, 4);
        $this->searchTreeSize   = $this->nodeCount * $this->nodeByteSize;
        $this->dataSectionStart = $this->searchTreeSize + self::DATA_SEPARATOR;
    }

    private function read(int $offset, int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        fseek($this->handle, $offset);
        $data = fread($this->handle, $length);
        return $data === false ? '' : $data;
    }
}

/**
 * Decoder for the MMDB data section. Returns [value, nextOffset] from decode().
 * pointerBase is the absolute file offset that internal pointers are relative to
 * (data section start for record data, metadata start for the metadata block).
 */
class MmdbDecoder
{
    /** @var resource */
    private $handle;
    private int $pointerBase;

    public function __construct($handle, int $pointerBase)
    {
        $this->handle      = $handle;
        $this->pointerBase = $pointerBase;
    }

    public function decode(int $offset): array
    {
        $ctrl = ord($this->read($offset, 1));
        $offset++;
        $type = $ctrl >> 5;

        if ($type === 0) {            // extended type
            $type = ord($this->read($offset, 1)) + 7;
            $offset++;
        }
        if ($type === 1) {            // pointer
            return $this->decodePointer($ctrl, $offset);
        }

        [$size, $offset] = $this->sizeFromCtrl($ctrl, $offset);

        switch ($type) {
            case 2:  // utf8 string
            case 4:  // bytes
                return [$this->read($offset, $size), $offset + $size];
            case 5:  // uint16
            case 6:  // uint32
            case 9:  // uint64
            case 10: // uint128
                return [$this->decodeUint($this->read($offset, $size)), $offset + $size];
            case 7:  // map
                return $this->decodeMap($offset, $size);
            case 11: // array
                return $this->decodeArray($offset, $size);
            case 8:  // int32
                return [$this->decodeInt32($this->read($offset, $size)), $offset + $size];
            case 14: // boolean
                return [$size !== 0, $offset];
            case 3:  // double
                $u = unpack('E', $this->read($offset, 8));
                return [$u[1] ?? 0.0, $offset + 8];
            case 15: // float
                $u = unpack('G', $this->read($offset, 4));
                return [$u[1] ?? 0.0, $offset + 4];
            default: // unsupported — skip
                return [null, $offset + $size];
        }
    }

    private function decodePointer(int $ctrl, int $offset): array
    {
        $size = ($ctrl >> 3) & 0x3;
        $bytes = $this->read($offset, $size + 1);
        $offset += $size + 1;

        if ($size === 0) {
            $p = (($ctrl & 0x7) << 8) | ord($bytes[0]);
        } elseif ($size === 1) {
            $p = (($ctrl & 0x7) << 16) | (ord($bytes[0]) << 8) | ord($bytes[1]);
            $p += 2048;
        } elseif ($size === 2) {
            $p = (($ctrl & 0x7) << 24) | (ord($bytes[0]) << 16) | (ord($bytes[1]) << 8) | ord($bytes[2]);
            $p += 526336;
        } else {
            $p = (ord($bytes[0]) << 24) | (ord($bytes[1]) << 16) | (ord($bytes[2]) << 8) | ord($bytes[3]);
        }

        [$value] = $this->decode($p + $this->pointerBase);
        return [$value, $offset];
    }

    private function sizeFromCtrl(int $ctrl, int $offset): array
    {
        $size = $ctrl & 0x1f;
        if ($size < 29) {
            return [$size, $offset];
        }
        if ($size === 29) {
            return [29 + ord($this->read($offset, 1)), $offset + 1];
        }
        if ($size === 30) {
            $b = $this->read($offset, 2);
            return [285 + ((ord($b[0]) << 8) | ord($b[1])), $offset + 2];
        }
        $b = $this->read($offset, 3);
        return [65821 + ((ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2])), $offset + 3];
    }

    private function decodeMap(int $offset, int $size): array
    {
        $map = [];
        for ($i = 0; $i < $size; $i++) {
            [$key, $offset] = $this->decode($offset);
            [$val, $offset] = $this->decode($offset);
            $map[(string) $key] = $val;
        }
        return [$map, $offset];
    }

    private function decodeArray(int $offset, int $size): array
    {
        $arr = [];
        for ($i = 0; $i < $size; $i++) {
            [$val, $offset] = $this->decode($offset);
            $arr[] = $val;
        }
        return [$arr, $offset];
    }

    private function decodeUint(string $bytes): int
    {
        $v = 0;
        $len = strlen($bytes);
        for ($i = 0; $i < $len; $i++) {
            $v = ($v << 8) | ord($bytes[$i]);
        }
        return $v;
    }

    private function decodeInt32(string $bytes): int
    {
        $v = $this->decodeUint($bytes);
        if (strlen($bytes) >= 4 && ($v & 0x80000000)) {
            $v -= 0x100000000;
        }
        return $v;
    }

    private function read(int $offset, int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        fseek($this->handle, $offset);
        $data = fread($this->handle, $length);
        return $data === false ? '' : $data;
    }
}
