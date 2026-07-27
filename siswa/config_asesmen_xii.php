<?php
return [
    'kode_tes' => 'asesmen_xii',
    'judul'    => 'Asesmen BK Kelas XII',
    'subjudul' => 'Perencanaan Karier dan Masa Depan Peserta Didik',

    'sections' => [

        'B' => [
            'title'  => 'Refleksi Diri',
            'fields' => [
                [
                    'key'     => 'perasaan_menjelang_lulus',
                    'type'    => 'radio',
                    'label'   => 'Bagaimana perasaan Anda menjelang kelulusan?',
                    'options' => ['Sangat siap', 'Siap', 'Masih ragu', 'Bingung', 'Belum memiliki rencana'],
                ],
                ['key' => 'hal_dibanggakan', 'type' => 'textarea', 'label' => 'Hal yang paling Anda banggakan selama belajar di SMK'],
                ['key' => 'prestasi_pernah_diperoleh', 'type' => 'textarea', 'label' => 'Prestasi yang pernah diperoleh'],
                ['key' => 'kompetensi_paling_dikuasai', 'type' => 'textarea', 'label' => 'Kompetensi yang paling Anda kuasai'],
                ['key' => 'kompetensi_perlu_ditingkatkan', 'type' => 'textarea', 'label' => 'Kompetensi yang masih perlu ditingkatkan'],
                [
                    'key'     => 'nilai_karakter_kelebihan',
                    'type'    => 'checkbox',
                    'label'   => 'Nilai atau karakter apa yang menjadi kelebihan Anda?',
                    'options' => ['Disiplin', 'Jujur', 'Bertanggung jawab', 'Kreatif', 'Komunikatif', 'Mudah bekerja sama', 'Kepemimpinan'],
                    'other'   => true,
                ],
            ],
        ],

        'C' => [
            'title'  => 'Pilihan Setelah Lulus',
            'fields' => [
                [
                    'key'     => 'rencana_utama',
                    'type'    => 'radio',
                    'label'   => 'Apa rencana utama Anda setelah lulus?',
                    'options' => ['Kuliah', 'Bekerja', 'Berwirausaha', 'Kuliah sambil bekerja', 'Belum menentukan'],
                ],
            ],
        ],

        'D' => [
            'title'       => 'Jika Memilih Kuliah',
            'conditional' => ['field' => 'rencana_utama', 'equals' => ['Kuliah', 'Kuliah sambil bekerja']],
            'fields'      => [
                ['key' => 'nama_pt_dituju', 'type' => 'text', 'label' => 'Nama Perguruan Tinggi yang dituju'],
                ['key' => 'program_studi_dituju', 'type' => 'text', 'label' => 'Program Studi'],
                ['key' => 'kota_pt_dituju', 'type' => 'text', 'label' => 'Kota'],
                [
                    'key'     => 'status_pt_dituju',
                    'type'    => 'radio',
                    'label'   => 'Negeri/Swasta',
                    'options' => ['Negeri', 'Swasta'],
                ],
                [
                    'key'     => 'sumber_info_kuliah',
                    'type'    => 'checkbox',
                    'label'   => 'Sudah mencari informasi melalui',
                    'options' => ['Website', 'Alumni', 'Guru BK', 'Media Sosial', 'Orang Tua', 'Teman'],
                ],
                [
                    'key'     => 'jalur_masuk_kuliah',
                    'type'    => 'checkbox',
                    'label'   => 'Jalur masuk yang akan diikuti',
                    'options' => ['SNBP', 'SNBT', 'Mandiri', 'Beasiswa', 'Kedinasan'],
                    'other'   => true,
                ],
                ['key' => 'alasan_pilih_prodi', 'type' => 'textarea', 'label' => 'Alasan memilih program studi tersebut'],
                [
                    'key'     => 'prodi_sesuai_jurusan_smk',
                    'type'    => 'radio',
                    'label'   => 'Apakah pilihan tersebut sesuai jurusan SMK?',
                    'options' => ['Ya', 'Tidak'],
                ],
                ['key' => 'alasan_prodi_sesuai_jurusan', 'type' => 'textarea', 'label' => 'Jelaskan alasannya'],
                [
                    'key'     => 'hambatan_kuliah',
                    'type'    => 'checkbox',
                    'label'   => 'Hambatan untuk kuliah',
                    'options' => ['Biaya', 'Nilai', 'Restu orang tua', 'Belum yakin', 'Informasi kurang'],
                    'other'   => true,
                ],
                [
                    'key'     => 'butuh_pendampingan_bk_kuliah',
                    'type'    => 'radio',
                    'label'   => 'Apakah membutuhkan pendampingan BK?',
                    'options' => ['Ya', 'Tidak'],
                ],
            ],
        ],

        'E' => [
            'title'       => 'Jika Memilih Bekerja',
            'conditional' => ['field' => 'rencana_utama', 'equals' => ['Bekerja', 'Kuliah sambil bekerja']],
            'fields'      => [
                ['key' => 'bidang_pekerjaan_diminati', 'type' => 'text', 'label' => 'Bidang pekerjaan yang diminati'],
                ['key' => 'posisi_diinginkan', 'type' => 'text', 'label' => 'Posisi yang diinginkan'],
                ['key' => 'kota_tempat_bekerja', 'type' => 'text', 'label' => 'Kota tempat bekerja'],
                ['key' => 'perusahaan_impian', 'type' => 'text', 'label' => 'Perusahaan impian'],
                [
                    'key'     => 'sudah_memiliki_dokumen_kerja',
                    'type'    => 'checkbox',
                    'label'   => 'Sudah memiliki',
                    'options' => ['CV', 'Surat Lamaran', 'Portofolio', 'Sertifikat Kompetensi', 'Sertifikat PKL', 'Akun LinkedIn', 'Email Profesional'],
                ],
                [
                    'key'     => 'jumlah_perusahaan_dilamar',
                    'type'    => 'radio',
                    'label'   => 'Berapa perusahaan yang akan dilamar?',
                    'options' => ['1', '2–5', '6–10', 'Lebih dari 10'],
                ],
                [
                    'key'     => 'bersedia_kerja_luar_daerah',
                    'type'    => 'radio',
                    'label'   => 'Apakah bersedia bekerja di luar daerah?',
                    'options' => ['Ya', 'Tidak', 'Menyesuaikan'],
                ],
                [
                    'key'     => 'gaji_diharapkan',
                    'type'    => 'radio',
                    'label'   => 'Gaji yang diharapkan',
                    'options' => ['< Rp2.000.000', 'Rp2–3 juta', 'Rp3–5 juta', '> Rp5 juta'],
                ],
                [
                    'key'     => 'hambatan_kerja',
                    'type'    => 'checkbox',
                    'label'   => 'Hambatan yang dirasakan',
                    'options' => ['Kurang percaya diri', 'Belum memiliki pengalaman', 'Belum membuat CV', 'Belum mengetahui lowongan', 'Kemampuan wawancara', 'Kemampuan komunikasi'],
                    'other'   => true,
                ],
                [
                    'key'     => 'pendampingan_dibutuhkan_kerja',
                    'type'    => 'checkbox',
                    'label'   => 'Pendampingan yang dibutuhkan',
                    'options' => ['Pembuatan CV', 'Simulasi Interview', 'Informasi Lowongan', 'Penguatan Mental Kerja', 'Soft Skill'],
                    'other'   => true,
                ],
            ],
        ],

        'F' => [
            'title'       => 'Jika Memilih Berwirausaha',
            'conditional' => ['field' => 'rencana_utama', 'equals' => ['Berwirausaha']],
            'fields'      => [
                ['key' => 'bidang_usaha_diminati', 'type' => 'text', 'label' => 'Bidang usaha yang diminati'],
                [
                    'key'     => 'sudah_punya_usaha',
                    'type'    => 'radio',
                    'label'   => 'Apakah sudah memiliki usaha?',
                    'options' => ['Ya', 'Tidak'],
                ],
                ['key' => 'nama_usaha', 'type' => 'text', 'label' => 'Nama usaha (jika ada)'],
                ['key' => 'produk_jasa', 'type' => 'text', 'label' => 'Produk/Jasa'],
                ['key' => 'target_pelanggan', 'type' => 'text', 'label' => 'Target pelanggan'],
                [
                    'key'     => 'modal_dimiliki',
                    'type'    => 'radio',
                    'label'   => 'Modal yang dimiliki',
                    'options' => ['Belum ada', '< Rp1 juta', 'Rp1–5 juta', '> Rp5 juta'],
                ],
                [
                    'key'     => 'sumber_modal',
                    'type'    => 'checkbox',
                    'label'   => 'Sumber modal',
                    'options' => ['Pribadi', 'Orang tua', 'Pinjaman', 'Investor', 'Program Pemerintah'],
                ],
                [
                    'key'     => 'media_promosi',
                    'type'    => 'checkbox',
                    'label'   => 'Media promosi yang akan digunakan',
                    'options' => ['Instagram', 'Facebook', 'TikTok', 'WhatsApp', 'Marketplace', 'Website'],
                ],
                [
                    'key'     => 'kendala_wirausaha',
                    'type'    => 'checkbox',
                    'label'   => 'Kendala yang diperkirakan',
                    'options' => ['Modal', 'Pemasaran', 'Produksi', 'SDM', 'Pengalaman'],
                    'other'   => true,
                ],
            ],
        ],

        'G' => [
            'title'  => 'Pengalaman dan Kompetensi',
            'fields' => [
                ['key' => 'tempat_pkl', 'type' => 'text', 'label' => 'PKL pernah dilakukan di'],
                ['key' => 'lama_pkl', 'type' => 'text', 'label' => 'Lama PKL'],
                [
                    'key'     => 'sertifikat_dimiliki',
                    'type'    => 'checkbox',
                    'label'   => 'Sertifikat yang dimiliki',
                    'options' => ['BNSP', 'Microsoft', 'Cisco', 'TOEIC', 'Pelatihan', 'Tidak ada'],
                ],
                ['key' => 'prestasi', 'type' => 'textarea', 'label' => 'Prestasi'],
                ['key' => 'organisasi', 'type' => 'textarea', 'label' => 'Organisasi'],
                ['key' => 'pengalaman_kepanitiaan', 'type' => 'textarea', 'label' => 'Pengalaman kepanitiaan'],
                [
                    'key'     => 'kemampuan_dimiliki',
                    'type'    => 'checkbox',
                    'label'   => 'Kemampuan yang dimiliki',
                    'options' => ['Microsoft Office', 'Desain Grafis', 'Editing Video', 'Pemrograman', 'Public Speaking', 'Bahasa Inggris', 'Digital Marketing', 'Akuntansi', 'Teknisi'],
                    'other'   => true,
                ],
            ],
        ],

        'H' => [
            'title'      => 'Skala Kesiapan Karier',
            'type'       => 'scale',
            'scale_min'  => 1,
            'scale_max'  => 5,
            'scale_note' => 'Berikan nilai 1–5 (1 = Sangat Tidak Setuju, 5 = Sangat Setuju).',
            'items'      => [
                ['key' => 'tahu_kelebihan_diri', 'label' => 'Saya mengetahui kelebihan diri saya'],
                ['key' => 'tahu_kekurangan_diri', 'label' => 'Saya mengetahui kekurangan diri saya'],
                ['key' => 'punya_tujuan_karier_jelas', 'label' => 'Saya mempunyai tujuan karier yang jelas'],
                ['key' => 'percaya_diri_hadapi_dunia_kerja', 'label' => 'Saya percaya diri menghadapi dunia kerja'],
                ['key' => 'siap_tes_kerja', 'label' => 'Saya siap mengikuti tes kerja'],
                ['key' => 'siap_wawancara_kerja', 'label' => 'Saya siap mengikuti wawancara kerja'],
                ['key' => 'siap_kuliah', 'label' => 'Saya siap kuliah'],
                ['key' => 'siap_wirausaha', 'label' => 'Saya siap berwirausaha'],
                ['key' => 'mampu_komunikasi_baik', 'label' => 'Saya mampu berkomunikasi dengan baik'],
                ['key' => 'mampu_kerja_tim', 'label' => 'Saya mampu bekerja dalam tim'],
                ['key' => 'mampu_selesaikan_masalah', 'label' => 'Saya mampu menyelesaikan masalah'],
                ['key' => 'disiplin_tanggung_jawab', 'label' => 'Saya disiplin dan bertanggung jawab'],
            ],
        ],

        'I' => [
            'title'  => 'Kebutuhan Layanan BK',
            'fields' => [
                [
                    'key'     => 'layanan_dibutuhkan_saat_ini',
                    'type'    => 'checkbox',
                    'label'   => 'Layanan yang paling Anda butuhkan saat ini',
                    'options' => ['Konsultasi Kuliah', 'Konsultasi Karier', 'Informasi Beasiswa', 'Pelatihan CV', 'Pelatihan Wawancara Kerja', 'Informasi Lowongan Kerja', 'Bimbingan Wirausaha', 'Pengembangan Soft Skill', 'Manajemen Stres Menjelang Lulus'],
                    'other'   => true,
                ],
            ],
        ],

        'J' => [
            'title'  => 'Harapan Siswa',
            'fields' => [
                ['key' => 'harapan_setelah_lulus', 'type' => 'textarea', 'label' => 'Apa harapan Anda setelah lulus dari SMK?'],
                ['key' => 'dukungan_sekolah_guru_bk', 'type' => 'textarea', 'label' => 'Dukungan apa yang Anda harapkan dari sekolah dan Guru BK agar cita-cita Anda dapat tercapai?'],
            ],
        ],

        'J2' => [
            'title'           => 'Rekomendasi Pembelajaran untuk Guru Mata Pelajaran dan Guru Produktif',
            'title_tampilan'  => 'Bagian J',
            'fields'          => [
                [
                    'key'         => 'matpel_paling_membantu',
                    'type'        => 'checkbox',
                    'label'       => 'A. Mata Pelajaran yang Paling Membantu Saya Belajar (pilih maksimal 3)',
                    'max_select'  => 3,
                    'options'     => ['Pendidikan Agama', 'PPKn', 'Bahasa Indonesia', 'Bahasa Inggris', 'Matematika', 'Informatika', 'PJOK', 'Sejarah', 'Mata Pelajaran Produktif', 'Projek P5'],
                    'other'       => true,
                ],
                [
                    'key'         => 'cara_belajar_disukai',
                    'type'        => 'checkbox',
                    'label'       => 'B. Cara Belajar yang Saya Sukai (pilih maksimal 3)',
                    'max_select'  => 3,
                    'options'     => ['Praktik langsung', 'Demonstrasi guru', 'Video pembelajaran', 'Diskusi kelompok', 'Simulasi dunia kerja', 'Project Based Learning', 'Studi kasus', 'Presentasi', 'Kunjungan industri', 'Pembelajaran berbasis masalah', 'Tugas individu', 'Tugas kelompok', 'Pembelajaran digital'],
                ],
                [
                    'key'     => 'bentuk_penugasan_disukai',
                    'type'    => 'checkbox',
                    'label'   => 'C. Bentuk Penugasan yang Membuat Saya Lebih Semangat',
                    'options' => ['Membuat produk', 'Membuat video', 'Membuat laporan', 'Presentasi', 'Praktik', 'Portofolio', 'Proyek kelompok', 'Studi lapangan', 'Simulasi pekerjaan'],
                ],
                [
                    'key'         => 'keterampilan_ingin_dilatih',
                    'type'        => 'checkbox',
                    'label'       => 'D. Keterampilan yang Ingin Lebih Banyak Dilatih di Sekolah (pilih maksimal 5)',
                    'max_select'  => 5,
                    'options'     => ['Public Speaking', 'Komunikasi', 'Kepemimpinan', 'Kerja Tim', 'Berpikir Kritis', 'Kreativitas', 'Problem Solving', 'Digital Skill', 'Penggunaan AI', 'Bahasa Inggris', 'Wawancara Kerja', 'Penyusunan CV', 'Etika Dunia Kerja', 'Pelayanan Pelanggan', 'Manajemen Waktu', 'Literasi Digital', 'Kewirausahaan', 'Pengelolaan Keuangan', 'Negosiasi'],
                    'other'       => true,
                ],
            ],
        ],

        'K' => [
            'title'  => 'Masukan untuk Guru Mata Pelajaran',
            'fields' => [
                [
                    'key'     => 'saran_agar_pembelajaran_menarik',
                    'type'    => 'checkbox',
                    'label'   => 'Menurut Anda, agar pembelajaran kelas XII lebih menarik dan bermanfaat, guru sebaiknya...',
                    'options' => ['Lebih banyak praktik', 'Lebih banyak diskusi', 'Menggunakan media digital', 'Menggunakan video pembelajaran', 'Mengaitkan materi dengan dunia kerja', 'Mengaitkan materi dengan kehidupan sehari-hari', 'Memberikan contoh kasus nyata', 'Mengundang praktisi industri', 'Memberikan proyek nyata', 'Memberikan umpan balik lebih sering'],
                    'other'   => true,
                ],
                ['key' => 'materi_ingin_dipelajari', 'type' => 'textarea', 'label' => 'Materi yang ingin lebih banyak dipelajari'],
                ['key' => 'pembelajaran_paling_berkesan', 'type' => 'textarea', 'label' => 'Pembelajaran yang paling berkesan selama di SMK'],
            ],
        ],

        'L' => [
            'title'  => 'Masukan untuk Guru Produktif',
            'fields' => [
                [
                    'key'     => 'saran_guru_produktif',
                    'type'    => 'checkbox',
                    'label'   => 'Menurut Anda, Guru Produktif sebaiknya lebih banyak memberikan...',
                    'options' => ['Praktik di bengkel/laboratorium', 'Simulasi pekerjaan', 'Sertifikasi kompetensi', 'Proyek industri', 'Studi kasus dunia kerja', 'Pelatihan penggunaan teknologi terbaru', 'Pelatihan software sesuai industri', 'Kunjungan industri', 'Kelas dari praktisi industri', 'Persiapan PKL lanjutan', 'Persiapan rekrutmen kerja', 'Persiapan tes kompetensi', 'Persiapan wawancara kerja', 'Penyusunan portofolio', 'Penyusunan CV'],
                    'other'   => true,
                ],
                ['key' => 'kompetensi_perlu_diperbanyak', 'type' => 'textarea', 'label' => 'Kompetensi apa yang menurut Anda masih perlu diperbanyak sebelum lulus?'],
                ['key' => 'fasilitas_praktik_ditingkatkan', 'type' => 'textarea', 'label' => 'Peralatan atau fasilitas praktik yang menurut Anda perlu ditingkatkan'],
                [
                    'key'     => 'pelatihan_diinginkan',
                    'type'    => 'checkbox',
                    'label'   => 'Pelatihan yang ingin diadakan sekolah',
                    'options' => ['Pelatihan AI untuk Dunia Kerja', 'Digital Marketing', 'Microsoft Office', 'Public Speaking', 'Bahasa Inggris untuk Kerja', 'Interview Kerja', 'Penyusunan CV', 'Editing Video', 'Desain Grafis', 'Pemrograman', 'Internet of Things (IoT)', 'PLC/Automation', 'Kewirausahaan', 'Branding Produk', 'Pengelolaan Keuangan'],
                    'other'   => true,
                ],
            ],
        ],

        'M' => [
            'title'  => 'Saran untuk Sekolah',
            'fields' => [
                ['key' => 'saran_peningkatan_sekolah', 'type' => 'textarea', 'label' => 'Apa yang perlu ditingkatkan oleh sekolah agar lulusan lebih siap menghadapi dunia kerja, dunia usaha, atau perguruan tinggi?'],
                ['key' => 'program_diharapkan_kelas_xii', 'type' => 'textarea', 'label' => 'Program apa yang paling Anda harapkan di kelas XII?'],
                ['key' => 'saran_untuk_guru_bk_dan_lainnya', 'type' => 'textarea', 'label' => 'Tuliskan saran Anda kepada Guru BK, Guru Mata Pelajaran, dan Guru Produktif agar pembelajaran lebih sesuai dengan kebutuhan siswa.'],
            ],
        ],

    ],
];