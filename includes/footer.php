</main>
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Hasello. Tüm hakları saklıdır.</p>
        </div>
    </footer>
    <script src="<?php echo url('js/script.js'); ?>"></script>

    <!-- Kart Detayları için Modal Pencere -->
    <div id="card-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <button class="modal-close-btn">&times;</button>
            <div id="modal-card-content-wrapper">
                <!-- Burası JavaScript tarafından doldurulacak -->
            </div>
        </div>
    </div>

    <!-- Toast Bildirimleri için Konteyner -->
    <div id="toast-container"></div>

    <?php
    // Session'da bir toast mesajı varsa, onu göster ve temizle
    if (isset($_SESSION['toast_message'])) {
        $toast_message = $_SESSION['toast_message'];
        echo "<script>document.addEventListener('DOMContentLoaded', () => { showToast('" . addslashes($toast_message['text']) . "', '" . addslashes($toast_message['type']) . "'); });</script>";
        unset($_SESSION['toast_message']);
    }
    ?>
</body>
</html>