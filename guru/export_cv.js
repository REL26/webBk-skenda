const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
    const idSiswa = process.argv[2];
    const outputPath = process.argv[3];

    const url = `http://localhost/websitebk-skenda/guru/cv_template.php?id_siswa=${idSiswa}`;

    const browser = await puppeteer.launch({
        headless: 'new'
    });

    const page = await browser.newPage();
    await page.goto(url, { waitUntil: 'networkidle0' });

    await page.pdf({
        path: outputPath,
        format: 'A4',
        printBackground: true
    });

    await browser.close();
})();
