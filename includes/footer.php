</div> <script src="../assets/vendor/jquery/jquery-3.7.0.min.js"></script>
    
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- SortableJS for drag-and-drop subject reordering -->
    <script src="../assets/vendor/sortable/Sortable.min.js"></script>
    <script>
    function initSortableSubjects() {
        document.querySelectorAll('.sortable-subjects').forEach(function(tbody) {
            // Cegah inisialisasi ganda
            if (tbody.dataset.sortableInit) return;
            // Jangan inisialisasi jika terkunci (ujian sedang berlangsung)
            if (tbody.dataset.locked === 'true') return;
            tbody.dataset.sortableInit = 'true';

            new Sortable(tbody, {
                animation: 200,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                filter: '.no-sort',
                onEnd: function(evt) {
                    const rows = tbody.querySelectorAll('tr[data-id]');
                    const order = Array.from(rows).map(row => row.dataset.id);
                    
                    fetch('modules/subjects/process.php?action=reorder', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order: order })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const movedRow = rows[evt.newIndex];
                            if (movedRow) {
                                movedRow.style.transition = 'background-color 0.5s';
                                movedRow.style.backgroundColor = '#d1e7dd';
                                setTimeout(() => { movedRow.style.backgroundColor = ''; }, 800);
                            }
                        } else {
                            alert('Gagal menyimpan urutan: ' + (data.message || ''));
                            window.location.reload();
                        }
                    })
                    .catch(err => {
                        console.error('Reorder error:', err);
                        alert('Terjadi kesalahan saat menyimpan urutan.');
                        window.location.reload();
                    });
                }
            });
        });
    }

    // Inisialisasi saat DOM ready
    document.addEventListener('DOMContentLoaded', initSortableSubjects);

    // Re-inisialisasi saat collapse panel dibuka (agar SortableJS bisa menghitung ukuran elemen)
    document.addEventListener('shown.bs.collapse', function(e) {
        initSortableSubjects();
    });
    </script>

    <script>
        $(document).ready(function() {
            // Logika Toggle Sidebar untuk Mobile
            $('#btn-toggle').on('click', function(e) {
                e.stopPropagation(); // Mencegah event bubbling
                $('#sidebar').toggleClass('active');
            });
            
            // Klik di area konten akan menutup sidebar jika sedang terbuka (khusus Mobile)
            $('.main-content').on('click', function() {
                if ($(window).width() < 992) {
                    $('#sidebar').removeClass('active');
                }
            });

            // Menutup sidebar otomatis jika layar di-resize ke ukuran desktop
            $(window).resize(function() {
                if ($(window).width() >= 992) {
                    $('#sidebar').removeClass('active');
                }
            });
        });
    </script>
    <!-- Quill.js WYSIWYG Editor -->
    <link href="../assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <script src="../assets/vendor/quill/quill.js"></script>
    
    <script>
        // Inisialisasi Quill.js
        document.addEventListener('DOMContentLoaded', function() {
            // Cek apakah ada element dengan id 'editor-container'
            const editorContainer = document.getElementById('editor-container');
            if (editorContainer) {
                // Ambil textarea yang akan disembunyikan
                const textarea = document.getElementById('editor');
                
                // Inisialisasi Quill
                const quill = new Quill('#editor-container', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'font': [] }],
                            [{ 'align': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['blockquote', 'code-block'],
                            ['link', 'image'],
                            ['clean']
                        ]
                    },
                    placeholder: 'Tulis isi bacaan di sini...'
                });
                
                // Set konten awal dari textarea (untuk mode edit)
                if (textarea.value) {
                    quill.root.innerHTML = textarea.value;
                }
                
                // Sinkronisasi Quill dengan textarea saat konten berubah
                quill.on('text-change', function() {
                    textarea.value = quill.root.innerHTML;
                });
                
                // Sinkronisasi sebelum form disubmit
                const form = textarea.closest('form');
                if (form) {
                    form.addEventListener('submit', function() {
                        textarea.value = quill.root.innerHTML;
                    });
                }
                
                console.log('Quill.js berhasil diinisialisasi');
            }

            // Auto-dismiss alert notifikasi setelah 3 detik
            setTimeout(function() {
                document.querySelectorAll('.alert.alert-dismissible').forEach(function(alertEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                        const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                        if (bsAlert) bsAlert.close();
                    } else if (window.jQuery) {
                        $(alertEl).fadeOut(300, function() { $(this).remove(); });
                    } else {
                        alertEl.style.transition = 'opacity 0.3s';
                        alertEl.style.opacity = '0';
                        setTimeout(() => alertEl.remove(), 300);
                    }
                });
            }, 3000);
        });
    </script>
</body>
</html>