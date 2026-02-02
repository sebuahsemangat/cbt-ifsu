</div> <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    
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
        });
    </script>
</body>
</html>