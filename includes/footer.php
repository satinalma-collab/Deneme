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

</body>
</html>