<?php
function secure_file_upload($file, $upload_dir) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "File upload error.";
    }

    // 1. Semak Saiz (Max 10MB)
    $max_size = 10 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return "File size exceeds 10MB limit.";
    }

    // 2. Semak MIME Type (Magic Bytes) - Bukan sekadar extension
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_mimes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
        'application/zip'
    ];

    if (!in_array($mime, $allowed_mimes)) {
        return "Invalid file type. Only PDF, DOCX, and ZIP are allowed.";
    }

    // 3. Tukar nama fail jadi rawak (Elak overwrite & path traversal)
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = bin2hex(random_bytes(16)) . '.' . $ext;
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $destination = $upload_dir . '/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $new_filename; // Return nama fail baru
    } else {
        return "Failed to save file.";
    }
}
?>