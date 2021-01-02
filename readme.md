# Program do indeksacji dokumentów elektronicznych

# Wymagania
  - PHP 8.0.0
  - Composer
  - Rozszerzenia PHP: mbstring, openssl
  - Ghostscript(do konwersji plików na wersję PDF 1.4)
  - QPDF(do dekodowania zawartości plików)

# Uruchamianie

1. Zaaktualizować pakiety Composera: `composer update`
2. Upewnić się że Ghostscript(komenda `gswin64c`) oraz QPDF(komenda `qpdf`) znajdują się w zmiennej środowiskowej PATH
2. Sprecyzować nazwę pliku źródłowego w pliku `index.php` w zmiennej `$fileName`
3. Uruchomić program: `php index.php`
