<?php
declare(strict_types=1);

/**
 * Minimal MaxMind MMDB reader — pure PHP, no extensions required.
 * Reads GeoLite2-Country.mmdb to resolve IPs → ISO 3166-1 country codes.
 *
 * Usage:
 *   $reader = new MmdbReader('/path/to/GeoLite2-Country.mmdb');
 *   $code   = $reader->country('103.118.78.200'); // "BD" or null
 */
class MmdbReader
{
    private string $data;
    private int    $nodeCount  = 0;
    private int    $recordSize = 0;
    private int    $nodeBytes  = 0;
    private int    $treeSize   = 0;
    private int    $dataStart  = 0;
    private int    $ipVersion  = 0;
    private array  $metaInfo   = [];

    private const META_MARKER = "\xab\xcd\xefMaxMind.com";

    public function __construct(string $path)
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException("Cannot read MMDB file: $path");
        }
        $this->data = (string) file_get_contents($path);
        if ($this->data === '') {
            throw new \RuntimeException("Empty or unreadable MMDB file: $path");
        }
        $this->parseMeta();
    }

    /* ── Public API ─────────────────────────────────────────────────── */

    /** Look up an IP and return the ISO country code, or null. */
    public function country(string $ip): ?string
    {
        $record = $this->lookup($ip);
        if (!is_array($record)) {
            return null;
        }
        return $record['country']['iso_code']
            ?? $record['registered_country']['iso_code']
            ?? null;
    }

    /** Return parsed metadata (build_epoch, database_type, etc.). */
    public function metadata(): array
    {
        return $this->metaInfo;
    }

    /* ── Metadata ───────────────────────────────────────────────────── */

    private function parseMeta(): void
    {
        $pos = strrpos($this->data, self::META_MARKER);
        if ($pos === false) {
            throw new \RuntimeException('Invalid MMDB: metadata marker not found');
        }
        $metaOffset = $pos + strlen(self::META_MARKER);
        [$meta]     = $this->decodeAt($metaOffset);

        if (!is_array($meta) || !isset($meta['node_count'], $meta['record_size'])) {
            throw new \RuntimeException('Invalid MMDB metadata');
        }

        $this->metaInfo   = $meta;
        $this->nodeCount  = (int) $meta['node_count'];
        $this->recordSize = (int) $meta['record_size'];
        $this->ipVersion  = (int) ($meta['ip_version'] ?? 6);
        $this->nodeBytes  = (int) ($this->recordSize * 2 / 8);
        $this->treeSize   = $this->nodeCount * $this->nodeBytes;
        $this->dataStart  = $this->treeSize + 16; // 16-byte NUL separator
    }

    /* ── IP lookup ──────────────────────────────────────────────────── */

    private function lookup(string $ip): ?array
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }

        // IPv4 in an IPv6 database: pad to 16 bytes (::x.x.x.x prefix).
        if (strlen($packed) === 4 && $this->ipVersion === 6) {
            $packed = str_pad($packed, 16, "\0", STR_PAD_LEFT);
        }

        $bitCount = strlen($packed) * 8;
        $node     = 0;

        for ($i = 0; $i < $bitCount; $i++) {
            if ($node >= $this->nodeCount) {
                break;
            }
            $bit  = (ord($packed[intdiv($i, 8)]) >> (7 - ($i % 8))) & 1;
            $node = $this->readRecord($node, $bit);
        }

        // node_count = not found.
        if ($node === $this->nodeCount || $node < $this->nodeCount) {
            return null;
        }

        // Resolve data pointer: absolute offset = treeSize + (record - nodeCount).
        $fileOffset = $this->treeSize + ($node - $this->nodeCount);
        [$result]   = $this->decodeAt($fileOffset);

        return is_array($result) ? $result : null;
    }

    /** Read left (bit=0) or right (bit=1) record from a search-tree node. */
    private function readRecord(int $node, int $bit): int
    {
        $off = $node * $this->nodeBytes;

        switch ($this->recordSize) {
            case 24:
                $o = $off + ($bit ? 3 : 0);
                return (ord($this->data[$o]) << 16)
                     | (ord($this->data[$o + 1]) << 8)
                     |  ord($this->data[$o + 2]);

            case 28:
                $mid = ord($this->data[$off + 3]);
                if ($bit === 0) {
                    return (($mid >> 4) << 24)
                         | (ord($this->data[$off]) << 16)
                         | (ord($this->data[$off + 1]) << 8)
                         |  ord($this->data[$off + 2]);
                }
                return (($mid & 0x0f) << 24)
                     | (ord($this->data[$off + 4]) << 16)
                     | (ord($this->data[$off + 5]) << 8)
                     |  ord($this->data[$off + 6]);

            case 32:
                $o = $off + ($bit ? 4 : 0);
                return (ord($this->data[$o]) << 24)
                     | (ord($this->data[$o + 1]) << 16)
                     | (ord($this->data[$o + 2]) << 8)
                     |  ord($this->data[$o + 3]);

            default:
                throw new \RuntimeException("Unsupported record size: {$this->recordSize}");
        }
    }

    /* ── Data decoder ───────────────────────────────────────────────── */

    /** Decode one value at an absolute byte offset. Returns [value, nextOffset]. */
    private function decodeAt(int $offset): array
    {
        $ctrl = ord($this->data[$offset]);
        $type = $ctrl >> 5;
        $offset++;

        // Type 1 = pointer (special).
        if ($type === 1) {
            return $this->decodePointer($ctrl, $offset);
        }

        // Type 0 = extended type (actual type stored in next byte + 7).
        if ($type === 0) {
            $type = ord($this->data[$offset]) + 7;
            $offset++;
        }

        // Payload size from lower 5 bits.
        $size = $ctrl & 0x1f;
        if ($size === 29) {
            $size = 29 + ord($this->data[$offset]);
            $offset++;
        } elseif ($size === 30) {
            $size = 285 + (ord($this->data[$offset]) << 8) + ord($this->data[$offset + 1]);
            $offset += 2;
        } elseif ($size === 31) {
            $size = 65821
                  + (ord($this->data[$offset]) << 16)
                  + (ord($this->data[$offset + 1]) << 8)
                  +  ord($this->data[$offset + 2]);
            $offset += 3;
        }

        return $this->decodeByType($type, $size, $offset);
    }

    private function decodePointer(int $ctrl, int $offset): array
    {
        $ptrSize = ($ctrl >> 3) & 3;
        $base    = $ctrl & 7;

        switch ($ptrSize) {
            case 0:
                $ptr = ($base << 8) | ord($this->data[$offset]);
                $offset++;
                break;
            case 1:
                $ptr = 2048 + (($base << 16) | (ord($this->data[$offset]) << 8) | ord($this->data[$offset + 1]));
                $offset += 2;
                break;
            case 2:
                $ptr = 526336 + (($base << 24) | (ord($this->data[$offset]) << 16) | (ord($this->data[$offset + 1]) << 8) | ord($this->data[$offset + 2]));
                $offset += 3;
                break;
            default: // 3
                $ptr = (ord($this->data[$offset]) << 24) | (ord($this->data[$offset + 1]) << 16) | (ord($this->data[$offset + 2]) << 8) | ord($this->data[$offset + 3]);
                $offset += 4;
                break;
        }

        // Resolve: pointer value is an offset from the start of the data section.
        [$value] = $this->decodeAt($this->dataStart + $ptr);
        return [$value, $offset];
    }

    private function decodeByType(int $type, int $size, int $offset): array
    {
        switch ($type) {
            case 2: // UTF-8 string
                return [substr($this->data, $offset, $size), $offset + $size];

            case 3: // double (8 bytes, big-endian)
                $v = unpack('E', substr($this->data, $offset, 8));
                return [$v[1], $offset + 8];

            case 4: // bytes
                return [substr($this->data, $offset, $size), $offset + $size];

            case 5: // uint16
            case 6: // uint32
            case 9: // uint64
            case 10: // uint128
                return [$this->readUint($offset, $size), $offset + $size];

            case 7: // map
                $map = [];
                for ($i = 0; $i < $size; $i++) {
                    [$key, $offset]   = $this->decodeAt($offset);
                    [$value, $offset] = $this->decodeAt($offset);
                    $map[(string) $key] = $value;
                }
                return [$map, $offset];

            case 8: // int32
                $v = $this->readUint($offset, $size);
                if ($size === 4 && $v >= 2147483648) {
                    $v -= 4294967296;
                }
                return [$v, $offset + $size];

            case 11: // array
                $arr = [];
                for ($i = 0; $i < $size; $i++) {
                    [$val, $offset] = $this->decodeAt($offset);
                    $arr[] = $val;
                }
                return [$arr, $offset];

            case 14: // boolean (size IS the value: 0 or 1)
                return [(bool) $size, $offset];

            case 15: // float (4 bytes, big-endian)
                $v = unpack('G', substr($this->data, $offset, 4));
                return [$v[1], $offset + 4];

            default:
                // Unknown type — skip its bytes.
                return [null, $offset + $size];
        }
    }

    /** Read an unsigned integer of $size bytes (big-endian). */
    private function readUint(int $offset, int $size): int
    {
        if ($size === 0) {
            return 0;
        }
        $val = 0;
        for ($i = 0; $i < $size; $i++) {
            $val = ($val << 8) | ord($this->data[$offset + $i]);
        }
        return $val;
    }
}
