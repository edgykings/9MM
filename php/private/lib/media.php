<?php
// Safe handling of admin uploads: type checked from the file's content, images re-encoded,
// random file names, stored in the uploads folder (where scripts are disabled).

/** Saves an uploaded image. Returns ['image' => 'uploads/x.jpg', 'thumb' => 'uploads/x-thumb.jpg'] or throws. */
function save_image_upload(array $file, int $maxW = 2000, int $thumbW = 720): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException(upload_error($file['error'] ?? 4));
    if ($file['size'] > 12 * 1024 * 1024) throw new RuntimeException('Image is larger than 12 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png' => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : false,
        default => throw new RuntimeException('Only JPG, PNG or WebP images can be uploaded.'),
    };
    if (!$src) throw new RuntimeException('That image could not be read.');
    $base = 'img-' . date('Ymd') . '-' . bin2hex(random_bytes(6));
    write_jpeg($src, UPLOADS_DIR . "/$base.jpg", $maxW);
    write_jpeg($src, UPLOADS_DIR . "/$base-thumb.jpg", $thumbW);
    imagedestroy($src);
    return ['image' => "uploads/$base.jpg", 'thumb' => "uploads/$base-thumb.jpg"];
}

function write_jpeg($src, string $dest, int $maxW): void {
    $w = imagesx($src); $h = imagesy($src);
    $nw = min($w, $maxW); $nh = (int)round($h * $nw / $w);
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // flatten transparency onto white
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imageinterlace($dst, true);
    if (!imagejpeg($dst, $dest, 82)) throw new RuntimeException('Could not save the image.');
    imagedestroy($dst);
    @chmod($dest, 0644);
}

/** Saves an uploaded PDF. Returns 'uploads/x.pdf' or throws. */
function save_pdf_upload(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException(upload_error($file['error'] ?? 4));
    if ($file['size'] > 40 * 1024 * 1024) throw new RuntimeException('PDF is larger than 40 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $head = (string)file_get_contents($file['tmp_name'], false, null, 0, 5);
    if ($mime !== 'application/pdf' || $head !== '%PDF-') throw new RuntimeException('Only PDF files can be uploaded here.');
    $name = '9minnutes-brochure-' . date('Ymd') . '-' . bin2hex(random_bytes(4)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], UPLOADS_DIR . "/$name")) throw new RuntimeException('Could not save the PDF.');
    @chmod(UPLOADS_DIR . "/$name", 0644);
    return "uploads/$name";
}

/** Deletes an uploaded file we created (never touches shipped assets). */
function delete_upload(?string $path): void {
    if (!$path || !str_starts_with($path, 'uploads/')) return;
    $name = basename($path);
    if (!preg_match('/^[a-z0-9-]+\.(jpg|pdf)$/', $name)) return;
    @unlink(UPLOADS_DIR . "/$name");
}

function upload_error(int $code): string {
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is larger than the server allows. Ask IT to raise upload_max_filesize.',
        UPLOAD_ERR_NO_FILE => 'No file was chosen.',
        UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Try again.',
        default => 'The upload failed (code ' . $code . ').',
    };
}
