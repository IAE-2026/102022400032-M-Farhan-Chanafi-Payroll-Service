# Prompt Engineering Log - Tugas 3 Payroll Service
## Prompt 1

Saya menanyakan ulang maksud dari Tugas 3 karena tugas ini ternyata masih berkaitan dengan progress Tugas Besar. Saya memastikan bahwa service yang digunakan tetap Payroll Service dari Tugas 2, bukan membuat project baru dari awal.

## Prompt 2

Saya mendiskusikan transaksi apa yang paling cocok dijadikan transaksi kritis pada Payroll Service. Dari hasil pengecekan, endpoint `POST /api/v1/payroll-runs` dipilih karena endpoint ini menjalankan proses payroll dan menghasilkan slip gaji.

## Prompt 3

Saya meminta bantuan untuk menyusun alasan kenapa proses payroll termasuk transaksi penting. Alasannya karena payroll berkaitan dengan data keuangan, total gaji, potongan absensi, dan penyimpanan slip gaji.

## Prompt 4

Saya mengecek bagian apa saja yang harus dihubungkan ke sistem pusat dosen. Dari situ saya memahami bahwa Tugas 3 berfokus pada SSO, SOAP Audit, dan RabbitMQ.

## Prompt 5

Saya meminta arahan awal untuk mulai progress dari service Tugas 2 yang sudah ada. Saya ingin progress terlihat bertahap, jadi dimulai dari koneksi ke SSO terlebih dahulu.

## Prompt 6

Saya menanyakan konfigurasi apa saja yang perlu ditambahkan di `.env` dan `.env.example` untuk kebutuhan Cloud Dosen, seperti URL cloud, API key, akun warga, dan team id.

## Prompt 7

Saya membuat service untuk koneksi ke Cloud SSO Dosen. Pada bagian ini saya mengecek cara request token menggunakan API key yang diberikan dosen.

## Prompt 8

Saya membuat endpoint test SSO untuk memastikan Payroll Service bisa mengambil token dari Cloud Dosen. Endpoint ini digunakan hanya untuk memastikan koneksi awal berjalan.

## Prompt 9

Saya mengalami error timeout ketika mencoba koneksi ke SSO dari Laravel. Saya meminta bantuan untuk membaca error tersebut dan mencari penyebabnya.

## Prompt 10

Saya mengecek koneksi Cloud SSO langsung dari Postman. Dari pengecekan ini diketahui bahwa server dosen aktif, tetapi koneksi dari Docker sempat bermasalah.

## Prompt 11

Saya memperbaiki konfigurasi koneksi Docker agar container bisa mengakses domain Cloud Dosen. Setelah itu request token SSO berhasil dan payload JWT bisa terbaca.

## Prompt 12

Saya mengecek hasil payload JWT dari SSO. Subject yang terbaca adalah `KEY-MHS-116`, lalu saya memetakan subject tersebut ke role lokal `HR_ADMIN`.

## Prompt 13

Saya lanjut mencoba SOAP Audit. Saya mengecek format SOAP yang diminta, terutama bagian `TeamID`, `ActivityName`, dan `LogContent`.

## Prompt 14

Saya membuat service untuk mengirim SOAP Audit. Data payroll yang awalnya berbentuk JSON disusun menjadi SOAP Envelope agar bisa dikirim ke endpoint audit dosen.

## Prompt 15

Saya mengetes SOAP Audit melalui Postman. Setelah berhasil, response menghasilkan `ReceiptNumber`, lalu saya menyesuaikan agar receipt tersebut bisa disimpan pada data payroll.

## Prompt 16

Saya lanjut ke bagian RabbitMQ. Saya membuat service untuk publish event payroll ke Cloud Dosen setelah proses payroll berhasil.

## Prompt 17

Saat test RabbitMQ, saya sempat mendapat error karena format body belum sesuai. Setelah dicek, body harus memiliki field `message`, lalu format payload saya perbaiki.

## Prompt 18

Saya melakukan test RabbitMQ ulang dan hasilnya berhasil. Event `payroll.processed` berhasil masuk ke exchange dosen dan muncul di board RabbitMQ.

## Prompt 19

Saya menggabungkan SSO, SOAP Audit, dan RabbitMQ ke endpoint asli `POST /api/v1/payroll-runs`, supaya integrasi tidak hanya berjalan di endpoint test.

## Prompt 20

Saya menemukan bug karena kode integrasi sempat diletakkan setelah `return`, sehingga bagian SSO, SOAP, dan RabbitMQ tidak ikut dijalankan. Setelah diperbaiki, flow payroll berjalan sampai selesai.

## Prompt 21

Saya melakukan test ulang endpoint `POST /api/v1/payroll-runs`. Hasilnya payroll berhasil diproses, role lokal terbaca `HR_ADMIN`, SOAP menghasilkan receipt number, dan RabbitMQ berhasil publish event.

## Prompt 22

Saya meminta bantuan untuk menyusun analisis Tugas 3, terutama bagian transaksi kritis, alasan pemilihan transaksi, role lokal, alur integrasi, dan hasil pengujian.

## Prompt 23

Saya membuat sequence diagram untuk menggambarkan alur Payroll Service saat terhubung ke SSO, database, SOAP Audit, dan RabbitMQ.

---
