<?php
return [
    'kode_tes' => 'asesmen_x',
    'judul'    => 'Asesmen BK Kelas X',
    'subjudul' => 'Mengenal Diri, Beradaptasi di SMK, dan Merancang Masa Depan',

    'sections' => [

        'B' => [
            'title'  => 'Adaptasi di SMK',
            'fields' => [
                [
                    'key'     => 'perasaan_jadi_siswa_smk',
                    'type'    => 'radio',
                    'label'   => 'Bagaimana perasaan Anda setelah menjadi siswa SMK?',
                    'options' => ['Sangat Senang', 'Senang', 'Biasa Saja', 'Masih Menyesuaikan', 'Kurang Nyaman'],
                ],
                [
                    'key'     => 'alasan_pilih_sekolah',
                    'type'    => 'checkbox',
                    'label'   => 'Mengapa memilih sekolah ini?',
                    'options' => ['Pilihan sendiri', 'Keinginan orang tua', 'Mengikuti teman', 'Dekat rumah', 'Prestasi sekolah'],
                    'other'   => true,
                ],
                [
                    'key'     => 'alasan_pilih_jurusan',
                    'type'    => 'checkbox',
                    'label'   => 'Mengapa memilih jurusan ini?',
                    'options' => ['Sesuai minat', 'Sesuai bakat', 'Peluang kerja', 'Keinginan orang tua', 'Mengikuti teman', 'Belum tahu'],
                    'other'   => true,
                ],
                [
                    'key'     => 'keyakinan_jurusan',
                    'type'    => 'radio',
                    'label'   => 'Seberapa yakin Anda dengan jurusan yang dipilih?',
                    'options' => ['Sangat yakin', 'Yakin', 'Cukup yakin', 'Masih ragu', 'Tidak yakin'],
                ],
                [
                    'key'     => 'kendala_masuk_smk',
                    'type'    => 'checkbox',
                    'label'   => 'Kendala yang Anda alami sejak masuk SMK',
                    'options' => ['Sulit memahami pelajaran', 'Sulit beradaptasi', 'Belum memiliki teman', 'Percaya diri', 'Ekonomi', 'Transportasi', 'Jarak rumah', 'Manajemen waktu'],
                    'other'   => true,
                ],
            ],
        ],

        'C' => [
            'title'  => 'Mengenal Diri',
            'fields' => [
                [
                    'key'     => 'kelebihan_diri',
                    'type'    => 'checkbox',
                    'label'   => 'Apa kelebihan yang Anda miliki?',
                    'options' => ['Disiplin', 'Jujur', 'Bertanggung jawab', 'Kreatif', 'Mudah bergaul', 'Komunikatif', 'Percaya diri', 'Teliti', 'Rajin', 'Cepat belajar'],
                    'other'   => true,
                ],
                [
                    'key'     => 'hal_ingin_ditingkatkan',
                    'type'    => 'checkbox',
                    'label'   => 'Hal yang ingin Anda tingkatkan',
                    'options' => ['Disiplin', 'Percaya diri', 'Komunikasi', 'Manajemen waktu', 'Belajar', 'Kerja sama', 'Public Speaking', 'Pengendalian emosi'],
                    'other'   => true,
                ],
                ['key' => 'hobi', 'type' => 'text', 'label' => 'Hobi'],
                ['key' => 'prestasi_pernah_diperoleh', 'type' => 'text', 'label' => 'Prestasi yang pernah diperoleh'],
                ['key' => 'cita_cita', 'type' => 'text', 'label' => 'Cita-cita'],
            ],
        ],

        'D' => [
            'title'  => 'Minat dan Potensi',
            'fields' => [
                [
                    'key'     => 'kegiatan_disukai',
                    'type'    => 'checkbox',
                    'label'   => 'Kegiatan yang paling Anda sukai',
                    'options' => ['Menggambar atau desain', 'Mengoperasikan komputer', 'Merakit atau memperbaiki alat', 'Memasak', 'Menulis', 'Berhitung', 'Bermain musik', 'Berolahraga', 'Berjualan', 'Berorganisasi', 'Membuat video atau konten', 'Membantu orang lain'],
                    'other'   => true,
                ],
                ['key' => 'bidang_ingin_dikuasai', 'type' => 'text', 'label' => 'Bidang yang ingin Anda kuasai selama di SMK'],
                ['key' => 'ekstrakurikuler_diminati', 'type' => 'text', 'label' => 'Kegiatan ekstrakurikuler yang ingin diikuti'],
            ],
        ],

        'E' => [
            'title'  => 'Kebiasaan Belajar',
            'fields' => [
                [
                    'key'     => 'lama_belajar_dirumah',
                    'type'    => 'radio',
                    'label'   => 'Lama belajar di rumah setiap hari',
                    'options' => ['Kurang dari 30 menit', '30–60 menit', '1–2 jam', 'Lebih dari 2 jam'],
                ],
                [
                    'key'     => 'sikap_saat_kesulitan_belajar',
                    'type'    => 'checkbox',
                    'label'   => 'Saat mengalami kesulitan belajar saya biasanya',
                    'options' => ['Bertanya kepada guru', 'Bertanya kepada teman', 'Mencari di internet', 'Belajar sendiri', 'Menunggu dijelaskan kembali', 'Tidak melakukan apa-apa'],
                ],
                [
                    'key'     => 'media_belajar',
                    'type'    => 'checkbox',
                    'label'   => 'Media belajar yang sering digunakan',
                    'options' => ['Buku', 'Modul', 'YouTube', 'ChatGPT', 'Google', 'TikTok Edukasi', 'Guru', 'Teman'],
                    'other'   => true,
                ],
            ],
        ],

        'F' => [
            'title'      => 'Karakter dan Kebiasaan',
            'type'       => 'scale',
            'scale_min'  => 1,
            'scale_max'  => 5,
            'scale_note' => 'Beri nilai 1–5.',
            'items'      => [
                ['key' => 'tepat_waktu_sekolah', 'label' => 'Saya datang ke sekolah tepat waktu.'],
                ['key' => 'tugas_tepat_waktu', 'label' => 'Saya menyelesaikan tugas tepat waktu.'],
                ['key' => 'berani_bertanya', 'label' => 'Saya berani bertanya ketika tidak memahami pelajaran.'],
                ['key' => 'mudah_kerja_sama', 'label' => 'Saya mudah bekerja sama dengan teman.'],
                ['key' => 'jaga_kebersihan_kelas', 'label' => 'Saya menjaga kebersihan kelas.'],
                ['key' => 'hormat_guru_teman', 'label' => 'Saya menghormati guru dan teman.'],
                ['key' => 'tanggung_jawab_tugas', 'label' => 'Saya bertanggung jawab terhadap tugas yang diberikan.'],
                ['key' => 'semangat_belajar', 'label' => 'Saya mempunyai semangat belajar.'],
                ['key' => 'kendalikan_emosi', 'label' => 'Saya mampu mengendalikan emosi.'],
                ['key' => 'terima_saran_kritik', 'label' => 'Saya bersedia menerima saran dan kritik.'],
            ],
        ],

        'G' => [
            'title'  => 'Rencana Masa Depan',
            'fields' => [
                [
                    'key'     => 'rencana_setelah_lulus',
                    'type'    => 'radio',
                    'label'   => 'Setelah lulus SMK saya ingin',
                    'options' => ['Kuliah', 'Bekerja', 'Berwirausaha', 'Kuliah sambil bekerja', 'Belum tahu'],
                ],
                ['key' => 'alasan_rencana', 'type' => 'text', 'label' => 'Alasan memilih'],
                ['key' => 'pekerjaan_dicita_citakan', 'type' => 'text', 'label' => 'Pekerjaan yang saya cita-citakan'],
                ['key' => 'pt_diminati', 'type' => 'text', 'label' => 'Perguruan tinggi yang ingin saya masuki (jika sudah ada)'],
            ],
        ],

        'H' => [
            'title'  => 'Kebutuhan Layanan BK',
            'fields' => [
                [
                    'key'     => 'layanan_dibutuhkan',
                    'type'    => 'checkbox',
                    'label'   => 'Layanan yang saya butuhkan',
                    'options' => ['Adaptasi di sekolah', 'Motivasi belajar', 'Manajemen waktu', 'Percaya diri', 'Konseling pribadi', 'Pengembangan bakat', 'Informasi karier', 'Perencanaan studi', 'Pendampingan akademik'],
                    'other'   => true,
                ],
            ],
        ],

        'I' => [
            'title'  => 'Rekomendasi Pembelajaran untuk Guru',
            'fields' => [
                [
                    'key'     => 'cara_guru_membantu_belajar',
                    'type'    => 'checkbox',
                    'label'   => 'Saya lebih mudah belajar jika guru',
                    'options' => ['Memberikan contoh nyata', 'Menggunakan video', 'Memberikan praktik', 'Menggunakan alat peraga', 'Mengadakan diskusi', 'Memberikan proyek', 'Menggunakan teknologi', 'Menghubungkan materi dengan dunia kerja', 'Memberikan latihan bertahap', 'Memberikan umpan balik'],
                ],
                [
                    'key'     => 'bentuk_tugas_disukai',
                    'type'    => 'checkbox',
                    'label'   => 'Bentuk tugas yang paling saya sukai',
                    'options' => ['Praktik', 'Proyek', 'Presentasi', 'Portofolio', 'Demonstrasi', 'Diskusi', 'Tes tertulis'],
                ],
                [
                    'key'     => 'keterampilan_ingin_dipelajari',
                    'type'    => 'checkbox',
                    'label'   => 'Keterampilan yang ingin lebih banyak dipelajari',
                    'options' => ['Komunikasi', 'Public Speaking', 'Kepemimpinan', 'Kerja Tim', 'Kreativitas', 'Berpikir Kritis', 'Literasi Digital', 'Penggunaan AI dalam belajar', 'Etika di Dunia Kerja', 'Kewirausahaan'],
                    'other'   => true,
                ],
            ],
        ],

        'J' => [
            'title'  => 'Harapan Siswa',
            'fields' => [
                ['key' => 'target_terbesar_di_smk', 'type' => 'textarea', 'label' => 'Apa target terbesar Anda selama belajar di SMK?'],
                ['key' => 'prestasi_ingin_dicapai', 'type' => 'textarea', 'label' => 'Prestasi apa yang ingin Anda capai?'],
                ['key' => 'dukungan_dari_guru_bk', 'type' => 'textarea', 'label' => 'Dukungan apa yang Anda harapkan dari Guru BK?'],
                ['key' => 'saran_untuk_guru', 'type' => 'textarea', 'label' => 'Saran Anda kepada guru mata pelajaran dan guru produktif agar pembelajaran lebih menyenangkan dan sesuai kebutuhan Anda.'],
            ],
        ],

    ],
];