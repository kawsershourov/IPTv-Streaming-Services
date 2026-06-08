</main>

<footer class="site-footer">
    <div class="wrap footer-inner">
        <p>&copy; <?= date('Y') ?> <?= e(Setting::get('site_name', 'SunPlex')) ?>. All rights reserved.</p>
        <p class="muted"><?= e(Setting::get('site_tagline', 'Live TV &amp; Streaming')) ?></p>
    </div>
</footer>

<script src="<?= e(asset('js/site.js')) ?>"></script>
<?= Setting::get('footer_code', '') // trusted admin-entered code ?>
</body>
</html>
