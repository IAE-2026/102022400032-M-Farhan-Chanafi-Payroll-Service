# Analisis Tugas 3 - Payroll Service

## 1. Analisis Transaksi Kritis

Pada Tugas 3 ini, transaksi yang saya pilih sebagai transaksi kritis adalah:

```http
POST /api/v1/payroll-runs
```

Endpoint ini dipakai untuk menjalankan proses payroll bulanan. Saya memilih endpoint ini karena prosesnya berhubungan langsung dengan perhitungan gaji karyawan, seperti gaji pokok, tunjangan tetap, potongan absensi, dan total gaji.

Endpoint ini juga termasuk transaksi yang mengubah data, karena setelah proses dijalankan sistem akan membuat atau memperbarui data slip gaji. Jadi menurut saya endpoint ini lebih cocok dijadikan transaksi kritis dibanding endpoint GET, karena GET hanya menampilkan data, sedangkan payroll run benar-benar menjalankan proses utama pada service.

Karena berhubungan dengan proses penggajian, transaksi ini perlu diamankan dan dicatat. Oleh karena itu, saya menghubungkan proses payroll dengan Cloud SSO, SOAP Audit, dan RabbitMQ.


## 2. Analisis Integrasi

Pada implementasi Tugas 3, Payroll Service tidak hanya memproses payroll secara lokal, tetapi juga terhubung dengan sistem pusat dosen.

Alur integrasi yang dibuat adalah:

1. Payroll Service mengambil token dari Cloud SSO Dosen.
2. Payload token dibaca untuk mendapatkan subject.
3. Subject `KEY-MHS-116` dipetakan ke role lokal `HR_ADMIN`.
4. Jika role sesuai, proses payroll dijalankan.
5. Data slip gaji disimpan ke database.
6. Data transaksi payroll dikirim ke SOAP Audit.
7. SOAP Audit mengembalikan `ReceiptNumber`.
8. `ReceiptNumber` disimpan ke data slip gaji.
9. Payroll Service mengirim event `payroll.processed` ke RabbitMQ.

Dari hasil pengujian, alur ini sudah berhasil berjalan dari endpoint asli `POST /api/v1/payroll-runs`, bukan hanya dari endpoint testing.


## 3. Analisis Sequence Diagram

Sequence diagram yang saya buat menggambarkan alur proses payroll dari awal sampai akhir.

Alurnya dimulai ketika HR Admin menjalankan proses payroll melalui halaman atau client. Request tersebut diteruskan ke `PayrollController`. Setelah request diterima, controller meminta token ke `IaeCloudSsoService`, lalu service tersebut menghubungi Cloud SSO Dosen.

Setelah token diterima, payload JWT dibaca dan subject dipetakan ke role lokal. Jika role yang didapat bukan `HR_ADMIN`, proses akan berhenti dan sistem menampilkan pesan akses ditolak. Jika role adalah `HR_ADMIN`, maka proses payroll dilanjutkan.

Pada proses payroll, sistem melakukan validasi data, menghitung total gaji, lalu menyimpan atau memperbarui slip gaji pada entity `PayrollSlip`. Setelah data slip gaji tersimpan, controller mengirim data transaksi ke `IaeSoapAuditService`. Service tersebut mengirim SOAP Envelope ke SOAP Audit Dosen.

Jika SOAP berhasil, sistem menerima `ReceiptNumber`, lalu receipt tersebut disimpan ke data slip gaji. Setelah itu controller memanggil `IaeRabbitMqPublisherService` untuk mengirim event `payroll.processed` ke RabbitMQ Dosen.

Di akhir proses, Payroll Service mengembalikan response sukses ke client berisi data slip gaji dan informasi hasil integrasi cloud.
