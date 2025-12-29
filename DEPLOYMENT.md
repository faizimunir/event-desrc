# Panduan Deployment - Event Desrc

## Masalah: ViteManifestNotFoundException

Error ini terjadi karena file manifest Vite (`public/build/manifest.json`) belum ada di server Ubuntu.

## Solusi: Build Assets di Server

### Langkah-langkah:

1. **SSH ke server Ubuntu Anda:**
   ```bash
   ssh user@your-server
   ```

2. **Masuk ke direktori aplikasi:**
   ```bash
   cd /var/www/event.desrc/event-desrc
   ```

3. **Pastikan Node.js dan npm terinstall dengan versi yang benar:**
   ```bash
   node --version
   npm --version
   ```
   
   ⚠️ **REQUIREMENT:** Node.js harus versi **20.19+** atau **22.12+** untuk Vite 7.x
   
   **Jika Node.js belum terinstall atau versi terlalu lama (< 20.19):**
   
   **Opsi A: Upgrade menggunakan NodeSource (Recommended)**
   ```bash
   # Hapus Node.js versi lama (jika ada)
   sudo apt-get remove nodejs npm -y
   
   # Install Node.js 20.x (LTS)
   curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
   sudo apt-get install -y nodejs
   
   # Verifikasi versi
   node --version  # Harus menunjukkan v20.19.0 atau lebih baru
   npm --version
   ```
   
   **Opsi B: Menggunakan NVM (Node Version Manager) - Lebih fleksibel**
   ```bash
   # Install NVM
   curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
   
   # Reload shell atau jalankan:
   export NVM_DIR="$HOME/.nvm"
   [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
   
   # Install Node.js 20 LTS
   nvm install 20
   nvm use 20
   nvm alias default 20
   
   # Verifikasi
   node --version
   npm --version
   ```

4. **Install dependencies (PENTING - harus dilakukan sebelum build!):**
   ```bash
   # Hapus node_modules dan package-lock.json lama (jika ada)
   rm -rf node_modules package-lock.json
   
   # Install dependencies
   npm install
   ```
   
   ⚠️ **WAJIB:** 
   - Pastikan `npm install` berhasil dan tidak ada error
   - Proses ini akan menginstall semua dependencies termasuk `vite` ke dalam folder `node_modules`
   - Setelah `npm install`, vite akan tersedia di `node_modules/.bin/vite`
   - **JANGAN** hanya mengandalkan `npx vite` - itu hanya untuk testing, bukan untuk build

5. **Verifikasi vite sudah terinstall dengan benar:**
   ```bash
   # Cek apakah vite ada di node_modules/.bin/
   ls -la node_modules/.bin/vite
   
   # Atau test dengan npx (ini akan menggunakan vite dari node_modules)
   npx vite --version
   
   # Yang paling penting: cek apakah npm bisa menemukan vite
   npm run build --dry-run  # atau langsung test
   ```
   
   ⚠️ **PENTING:** Jika `npx vite --version` berhasil tapi `npm run build` gagal, berarti `node_modules` belum terinstall dengan benar. Jalankan `npm install` lagi.

6. **Build assets untuk production:**
   ```bash
   npm run build
   ```
   
   ⚠️ **JANGAN gunakan `sudo` untuk npm run build** kecuali jika benar-benar diperlukan. Jika perlu sudo, pastikan ownership folder sudah benar.

7. **Pastikan permissions benar:**
   ```bash
   sudo chown -R www-data:www-data public/build
   sudo chmod -R 755 public/build
   ```

8. **Restart web server (jika perlu):**
   ```bash
   sudo systemctl restart nginx  # atau apache2
   ```

## Alternatif: Build Sebelum Deploy

Jika Anda tidak ingin build di server, Anda bisa:

1. **Build di local:**
   ```bash
   npm run build
   ```

2. **Upload folder `public/build` ke server:**
   ```bash
   scp -r public/build user@server:/var/www/event.desrc/event-desrc/public/
   ```

## Best Practice untuk Deployment Otomatis

Tambahkan build step ke deployment script Anda:

```bash
#!/bin/bash
# deploy.sh

cd /var/www/event.desrc/event-desrc
git pull origin main
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php8.3-fpm
```

## Troubleshooting

### Error: "vite: not found" saat `npm run build`

**Penyebab:** 
- Dependencies npm belum terinstall dengan benar di `node_modules`
- `npx vite --version` berhasil karena `npx` download vite sementara, tapi `npm run build` butuh vite di `node_modules/.bin/vite`
- Setelah upgrade Node.js, `node_modules` mungkin masih menggunakan versi lama atau corrupt

**Gejala:**
```bash
# Ini berhasil:
npx vite --version  # ✅ vite/7.3.0

# Tapi ini gagal:
npm run build  # ❌ sh: 1: vite: not found
```

**Solusi Lengkap:**
```bash
# 1. Pastikan Anda di direktori yang benar
cd /var/www/event.desrc/event-desrc

# 2. Hapus node_modules dan package-lock.json (WAJIB untuk fresh install)
rm -rf node_modules package-lock.json

# 3. Pastikan Node.js versi benar
node --version  # Harus 20.19+ atau 22.12+

# 4. Install dependencies (ini akan install vite ke node_modules)
npm install

# 5. Verifikasi vite terinstall di node_modules
ls -la node_modules/.bin/vite
# Harus menampilkan file vite, bukan "No such file or directory"

# 6. Test dengan npx (seharusnya menggunakan vite dari node_modules sekarang)
npx vite --version

# 7. Build assets
npm run build

# 8. Set permissions
sudo chown -R www-data:www-data public/build
sudo chmod -R 755 public/build
```

**Jika masih gagal setelah npm install:**

```bash
# Cek apakah npm cache corrupt
npm cache clean --force

# Install ulang
rm -rf node_modules package-lock.json
npm install

# Cek PATH npm
npm bin
which npm

# Coba dengan full path
./node_modules/.bin/vite build
```

### Error: "ETIMEDOUT" atau "network connectivity" saat npm install

**Penyebab:** 
- Koneksi internet lambat atau tidak stabil
- Server berada di belakang proxy/firewall
- npm registry (registry.npmjs.org) tidak bisa diakses dari server
- Timeout terlalu pendek untuk koneksi lambat

**Contoh Error:**
```
npm error code ETIMEDOUT
npm error errno ETIMEDOUT
npm error network request to https://registry.npmjs.org/@tailwindcss%2fvite failed
npm error network This is a problem related to network connectivity.
```

**Solusi:**

**Metode 1: Gunakan Registry Mirror (Recommended untuk Indonesia/Asia)**
```bash
# Gunakan registry mirror yang lebih cepat (contoh: Taobao untuk China, atau registry lokal)
# Untuk Indonesia, bisa coba registry ini:
npm config set registry https://registry.npmmirror.com

# Atau gunakan registry npm resmi dengan timeout lebih lama
npm config set registry https://registry.npmjs.org/
npm config set fetch-timeout 60000
npm config set fetch-retries 5
npm config set fetch-retry-mintimeout 20000

# Install dependencies
npm install
```

**Metode 2: Install dengan Retry dan Timeout Lebih Lama**
```bash
# Set timeout lebih lama
npm config set fetch-timeout 300000  # 5 menit
npm config set fetch-retries 10
npm config set fetch-retry-mintimeout 30000

# Install dengan verbose untuk melihat progress
npm install --verbose

# Atau install dengan retry manual
npm install || npm install || npm install
```

**Metode 3: Install dengan Proxy (jika server di belakang proxy)**
```bash
# Set proxy (ganti dengan proxy server Anda)
npm config set proxy http://proxy-server:port
npm config set https-proxy http://proxy-server:port

# Atau set di environment variable
export HTTP_PROXY=http://proxy-server:port
export HTTPS_PROXY=http://proxy-server:port

# Install dependencies
npm install
```

**Metode 4: Install dari Local (Build di Local, Upload ke Server)**
```bash
# Di komputer local (Windows/XAMPP):
cd C:\xampp\htdocs\event-desrc
npm install
npm run build

# Upload folder node_modules dan public/build ke server
# Menggunakan SCP atau SFTP:
scp -r node_modules user@server:/var/www/event.desrc/event-desrc/
scp -r public/build user@server:/var/www/event.desrc/event-desrc/public/

# Atau hanya upload public/build (lebih cepat):
# Karena node_modules tidak diperlukan di production, cukup upload build files
scp -r public/build user@server:/var/www/event.desrc/event-desrc/public/
```

**Metode 5: Gunakan Yarn sebagai Alternatif**
```bash
# Install Yarn
npm install -g yarn

# Atau install via apt
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt-get update && sudo apt-get install yarn

# Install dependencies dengan Yarn (biasanya lebih stabil untuk koneksi lambat)
yarn install

# Build dengan Yarn
yarn build
```

**Metode 6: Install Package Satu per Satu (Jika Timeout Terjadi pada Package Tertentu)**
```bash
# Install package yang error secara manual
npm install @tailwindcss/vite --verbose
npm install vite --verbose
npm install laravel-vite-plugin --verbose

# Setelah semua package utama terinstall, install sisanya
npm install
```

**Cek Konfigurasi npm:**
```bash
# Lihat konfigurasi saat ini
npm config list

# Reset ke default (jika perlu)
npm config delete registry
npm config delete proxy
npm config delete https-proxy
```

**Tips:**
- Jika koneksi sangat lambat, pertimbangkan untuk build di local dan upload `public/build` saja
- `node_modules` tidak perlu di-upload ke production server (hanya butuh `public/build`)
- Gunakan `--verbose` untuk melihat progress dan menemukan package mana yang timeout

### Error: "EACCES: permission denied" saat npm install

**Penyebab:** 
- User tidak memiliki permission untuk menulis di direktori `/var/www/`
- Folder dimiliki oleh `www-data` atau `root`, bukan user yang menjalankan npm
- Permission folder terlalu ketat

**Contoh Error:**
```
npm error code EACCES
npm error syscall mkdir
npm error path /var/www/event.desrc/event-desrc/node_modules
npm error errno -13
npm error Error: EACCES: permission denied, mkdir '/var/www/event.desrc/event-desrc/node_modules'
```

**Solusi:**

**Metode 1: Ubah Ownership Folder ke User (Recommended)**
```bash
# Ubah ownership folder aplikasi ke user Anda (ganti 'eljo' dengan username Anda)
sudo chown -R eljo:eljo /var/www/event.desrc/event-desrc

# Atau jika ingin tetap menggunakan www-data untuk web server:
sudo chown -R eljo:www-data /var/www/event.desrc/event-desrc
sudo chmod -R 775 /var/www/event.desrc/event-desrc

# Tambahkan user ke group www-data (opsional, untuk akses bersama)
sudo usermod -a -G www-data eljo

# Logout dan login lagi, atau:
newgrp www-data

# Sekarang npm install seharusnya berhasil
npm install
npm run build
```

**Metode 2: Install di Home Directory, Lalu Copy (Alternatif)**
```bash
# Install di home directory (tidak perlu sudo)
cd ~
mkdir -p temp-build
cd temp-build
cp -r /var/www/event.desrc/event-desrc/package.json .
cp -r /var/www/event.desrc/event-desrc/package-lock.json . 2>/dev/null || true
cp -r /var/www/event.desrc/event-desrc/vite.config.js .

# Install dependencies
npm install

# Build assets
npm run build

# Copy hasil build ke direktori aplikasi
sudo cp -r public/build /var/www/event.desrc/event-desrc/public/
sudo chown -R www-data:www-data /var/www/event.desrc/event-desrc/public/build
sudo chmod -R 755 /var/www/event.desrc/event-desrc/public/build

# Bersihkan
cd ~
rm -rf temp-build
```

**Metode 3: Build di Local, Upload ke Server (Paling Mudah)**
```bash
# Di komputer local (Windows/XAMPP):
cd C:\xampp\htdocs\event-desrc
npm install
npm run build

# Upload folder public/build ke server menggunakan SCP atau SFTP
# Dari local Windows (PowerShell atau Git Bash):
scp -r public/build eljo@drc:/var/www/event.desrc/event-desrc/public/

# Di server, set permissions:
sudo chown -R www-data:www-data /var/www/event.desrc/event-desrc/public/build
sudo chmod -R 755 /var/www/event.desrc/event-desrc/public/build
```

**Metode 4: Gunakan sudo (Tidak Disarankan, Tapi Bisa)**
```bash
# ⚠️ WARNING: Menggunakan sudo untuk npm install bisa menyebabkan masalah permission di node_modules
# Lebih baik gunakan Metode 1 atau 3

# Jika terpaksa menggunakan sudo:
sudo npm install
sudo npm run build

# Setelah itu, ubah ownership kembali:
sudo chown -R www-data:www-data /var/www/event.desrc/event-desrc/public/build
sudo chmod -R 755 /var/www/event.desrc/event-desrc/public/build
```

**Cek Permission Saat Ini:**
```bash
# Cek ownership folder
ls -la /var/www/event.desrc/event-desrc/

# Cek permission
stat /var/www/event.desrc/event-desrc/

# Cek user dan group saat ini
whoami
groups
```

**Best Practice:**
- Untuk development: ubah ownership ke user developer (Metode 1)
- Untuk production: build di local dan upload `public/build` saja (Metode 3)
- Jangan gunakan `sudo npm install` karena bisa menyebabkan masalah permission di `node_modules`

### Error: "npm WARN EBADENGINE" atau "Unsupported engine"

**Penyebab:** Node.js versi terlalu lama. Vite 7.x memerlukan Node.js 20.19+ atau 22.12+.

**Contoh Error:**
```
npm WARN EBADENGINE Unsupported engine {
npm WARN EBADENGINE   package: 'vite@7.3.0',
npm WARN EBADENGINE   required: { node: '^20.19.0 || >=22.12.0' },
npm WARN EBADENGINE   current: { node: 'v18.19.1', npm: '9.2.0' }
npm WARN EBADENGINE }
```

**Solusi - Upgrade Node.js:**

**Metode 1: Menggunakan NodeSource (Paling Mudah)**
```bash
# Hapus Node.js versi lama
sudo apt-get remove nodejs npm -y
sudo apt-get autoremove -y

# Install Node.js 20.x (LTS)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verifikasi versi (harus v20.19.0 atau lebih baru)
node --version
npm --version

# Sekarang install dependencies lagi
npm install
npm run build
```

**Metode 2: Menggunakan NVM (Lebih Fleksibel)**
```bash
# Install NVM
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Reload shell
source ~/.bashrc
# atau jika menggunakan zsh:
# source ~/.zshrc

# Install dan gunakan Node.js 20
nvm install 20
nvm use 20
nvm alias default 20

# Verifikasi
node --version  # Harus v20.19.0+
npm --version

# Install dependencies
npm install
npm run build
```

**Catatan:** Setelah upgrade Node.js, hapus `node_modules` dan `package-lock.json` lalu install ulang:
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Error: "Permission denied"

**Solusi:**
```bash
# Ubah ownership folder aplikasi (ganti 'user' dengan username Anda)
sudo chown -R $USER:$USER /var/www/event.desrc/event-desrc

# Atau jika menggunakan www-data:
sudo chown -R www-data:www-data /var/www/event.desrc/event-desrc
sudo chmod -R 755 /var/www/event.desrc/event-desrc
```

### Error: "EACCES: permission denied"

**Solusi:**
```bash
# Fix npm permissions (jika menggunakan user biasa)
mkdir ~/.npm-global
npm config set prefix '~/.npm-global'
echo 'export PATH=~/.npm-global/bin:$PATH' >> ~/.bashrc
source ~/.bashrc

# Atau gunakan nvm untuk manage Node.js
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 20
nvm use 20
```

