<?php


return [
    'kode_tes' => 'asesmen_xi',
    'judul'    => 'Asesmen BK Kelas XI',
    'subjudul' => 'Pengembangan Kompetensi, Soft Skill, dan Persiapan Karier',

    'sections' => [

        'B' => [
            'title'  => 'Evaluasi Perkembangan Belajar',
            'fields' => [
                [
                    'key'     => 'perkembangan_belajar',
                    'type'    => 'radio',
                    'label'   => 'Bagaimana perkembangan belajar Anda selama kelas XI?',
                    'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'],
                ],
                ['key' => 'matpel_paling_dikuasai', 'type' => 'text', 'label' => 'Mata pelajaran yang paling Anda kuasai'],
                ['key' => 'matpel_masih_tantangan', 'type' => 'text', 'label' => 'Mata pelajaran yang masih menjadi tantangan'],
                [
                    'key'     => 'prestasi_selama_kelas_xi',
                    'type'    => 'checkbox',
                    'label'   => 'Prestasi yang telah Anda capai selama kelas XI',
                    'options' => ['Akademik', 'Non Akademik', 'Sertifikasi', 'Lomba', 'Organisasi', 'Belum Ada'],
                ],
                ['key' => 'penjelasan_prestasi', 'type' => 'textarea', 'label' => 'Jelaskan prestasi yang dipilih di atas'],
                [
                    'key'     => 'kompetensi_paling_berkembang',
                    'type'    => 'checkbox',
                    'label'   => 'Kompetensi yang paling berkembang',
                    'options' => ['Komunikasi', 'Kepemimpinan', 'Kerja Tim', 'Problem Solving', 'Kreativitas', 'Literasi Digital', 'Kompetensi Kejuruan', 'Public Speaking'],
                    'other'   => true,
                ],
            ],
        ],

        'C' => [
            'title'  => 'Pengalaman PKL / Praktik Kerja Lapangan',
            'fields' => [
                [
                    'key'     => 'sudah_pkl',
                    'type'    => 'radio',
                    'label'   => 'Apakah Anda sudah melaksanakan PKL?',
                    'options' => ['Ya', 'Belum'],
                ],
                ['key' => 'nama_instansi_pkl', 'type' => 'text', 'label' => 'Nama Instansi/Perusahaan'],
                ['key' => 'bidang_pekerjaan_pkl', 'type' => 'text', 'label' => 'Bidang pekerjaan selama PKL'],
                ['key' => 'hal_dipelajari_pkl', 'type' => 'textarea', 'label' => 'Apa yang paling banyak Anda pelajari selama PKL?'],
                [
                    'key'     => 'kompetensi_meningkat_pkl',
                    'type'    => 'checkbox',
                    'label'   => 'Kompetensi yang meningkat setelah PKL',
                    'options' => ['Disiplin', 'Komunikasi', 'Kerja Sama', 'Pelayanan', 'Penggunaan Teknologi', 'Penyelesaian Masalah', 'Administrasi', 'Kompetensi Keahlian'],
                    'other'   => true,
                ],
                [
                    'key'     => 'kendala_pkl',
                    'type'    => 'checkbox',
                    'label'   => 'Kendala selama PKL',
                    'options' => ['Adaptasi', 'Komunikasi', 'Disiplin', 'Percaya Diri', 'Penguasaan Kompetensi', 'Transportasi'],
                    'other'   => true,
                ],
            ],
        ],

        'D' => [
            'title'      => 'Soft Skill',
            'type'       => 'scale',
            'scale_min'  => 1,
            'scale_max'  => 5,
            'scale_note' => 'Berikan nilai 1–5.',
            'items'      => [
                ['key' => 'kerja_sama_tim', 'label' => 'Saya mampu bekerja sama dalam tim.'],
                ['key' => 'sampaikan_pendapat', 'label' => 'Saya mampu menyampaikan pendapat dengan baik.'],
                ['key' => 'tanggung_jawab_tugas', 'label' => 'Saya bertanggung jawab terhadap tugas.'],
                ['key' => 'atur_waktu', 'label' => 'Saya mampu mengatur waktu.'],
                ['key' => 'selesaikan_masalah', 'label' => 'Saya mampu menyelesaikan masalah.'],
                ['key' => 'terima_kritik', 'label' => 'Saya mampu menerima kritik.'],
                ['key' => 'memimpin_kelompok', 'label' => 'Saya mampu memimpin kelompok.'],
                ['key' => 'percaya_diri_bicara_umum', 'label' => 'Saya percaya diri berbicara di depan umum.'],
                ['key' => 'mudah_adaptasi', 'label' => 'Saya mudah beradaptasi dengan lingkungan baru.'],
                ['key' => 'etika_baik', 'label' => 'Saya memiliki etika yang baik.'],
            ],
        ],

        'E' => [
            'title'  => 'Rencana Karier',
            'fields' => [
                [
                    'key'     => 'rencana_setelah_lulus',
                    'type'    => 'radio',
                    'label'   => 'Setelah lulus saya berencana',
                    'options' => ['Kuliah', 'Bekerja', 'Berwirausaha', 'Kuliah sambil bekerja', 'Belum menentukan'],
                ],
                ['key' => 'alasan_rencana_karier', 'type' => 'textarea', 'label' => 'Mengapa memilih pilihan tersebut?'],
                ['key' => 'bidang_prodi_diminati', 'type' => 'text', 'label' => 'Bidang pekerjaan atau program studi yang diminati'],
                [
                    'key'     => 'persiapan_mulai_sekarang',
                    'type'    => 'checkbox',
                    'label'   => 'Hal yang perlu saya persiapkan mulai sekarang',
                    'options' => ['Nilai Akademik', 'Sertifikat Kompetensi', 'Bahasa Inggris', 'Public Speaking', 'CV', 'Portofolio', 'Wawancara Kerja', 'Tes Masuk Perguruan Tinggi', 'Kewirausahaan'],
                    'other'   => true,
                ],
            ],
        ],

        'F' => [
            'title'  => 'Organisasi dan Pengembangan Diri',
            'fields' => [
                [
                    'key'     => 'organisasi_diikuti',
                    'type'    => 'checkbox',
                    'label'   => 'Saya mengikuti',
                    'options' => ['OSIS', 'MPK', 'Pramuka', 'PMR', 'PIK-R', 'Ekstrakurikuler', 'Organisasi luar sekolah', 'Tidak mengikuti organisasi'],
                ],
                ['key' => 'pelatihan_pernah_diikuti', 'type' => 'text', 'label' => 'Pelatihan yang pernah diikuti'],
                [
                    'key'     => 'sertifikat_dimiliki',
                    'type'    => 'checkbox',
                    'label'   => 'Sertifikat yang dimiliki',
                    'options' => ['BNSP', 'TOEIC', 'Microsoft', 'Cisco', 'Pelatihan', 'Seminar', 'Belum Ada'],
                ],
            ],
        ],

        'G' => [
            'title'  => 'Kebutuhan Layanan BK',
            'fields' => [
                [
                    'key'     => 'layanan_dibutuhkan',
                    'type'    => 'checkbox',
                    'label'   => 'Saya membutuhkan layanan',
                    'options' => ['Konseling Individu', 'Konseling Kelompok', 'Motivasi Belajar', 'Persiapan PKL', 'Persiapan Karier', 'Informasi Perguruan Tinggi', 'Pengembangan Soft Skill', 'Manajemen Stres', 'Pengembangan Bakat'],
                    'other'   => true,
                ],
            ],
        ],

        'H' => [
            'title'  => 'Rekomendasi Pembelajaran untuk Guru Mata Pelajaran dan Guru Produktif',
            'fields' => [
                [
                    'key'     => 'model_pembelajaran_membantu',
                    'type'    => 'checkbox',
                    'label'   => 'Model pembelajaran yang paling membantu saya',
                    'options' => ['Praktik', 'Project Based Learning', 'Studi Kasus', 'Simulasi Dunia Kerja', 'Demonstrasi', 'Diskusi', 'Pembelajaran Digital', 'Kunjungan Industri', 'Praktisi Mengajar'],
                ],
                [
                    'key'     => 'saran_guru_matpel',
                    'type'    => 'checkbox',
                    'label'   => 'Guru Mata Pelajaran sebaiknya lebih banyak',
                    'options' => ['Memberikan contoh nyata', 'Menghubungkan materi dengan dunia kerja', 'Menggunakan video pembelajaran', 'Memberikan proyek', 'Mengadakan diskusi', 'Memberikan latihan bertahap', 'Menggunakan teknologi pembelajaran', 'Memberikan umpan balik terhadap hasil belajar'],
                ],
                [
                    'key'     => 'saran_guru_produktif',
                    'type'    => 'checkbox',
                    'label'   => 'Guru Produktif sebaiknya lebih banyak',
                    'options' => ['Praktik di laboratorium/bengkel', 'Simulasi pekerjaan', 'Menggunakan peralatan industri', 'Mengajarkan software terbaru', 'Menyusun portofolio', 'Menyiapkan sertifikasi', 'Simulasi wawancara kerja', 'Penyusunan CV', 'Menghadirkan praktisi industri', 'Kunjungan industri'],
                ],
                [
                    'key'     => 'keterampilan_ingin_dipelajari',
                    'type'    => 'checkbox',
                    'label'   => 'Keterampilan yang masih ingin saya pelajari',
                    'options' => ['Public Speaking', 'Digital Skill', 'Artificial Intelligence (AI)', 'Editing Video', 'Desain Grafis', 'Pemrograman', 'Bahasa Inggris', 'Leadership', 'Entrepreneurship', 'Digital Marketing', 'Pengelolaan Keuangan'],
                    'other'   => true,
                ],
            ],
        ],

        'I' => [
            'title'  => 'Harapan Siswa',
            'fields' => [
                ['key' => 'target_sebelum_lulus', 'type' => 'textarea', 'label' => 'Target yang ingin saya capai sebelum lulus'],
                ['key' => 'kompetensi_ingin_dikuasai', 'type' => 'textarea', 'label' => 'Kompetensi yang ingin saya kuasai'],
                ['key' => 'dukungan_dari_guru_bk', 'type' => 'textarea', 'label' => 'Dukungan yang saya harapkan dari Guru BK'],
                ['key' => 'saran_untuk_guru', 'type' => 'textarea', 'label' => 'Saran kepada Guru Mata Pelajaran dan Guru Produktif agar pembelajaran lebih sesuai kebutuhan saya'],
            ],
        ],

    ],
];