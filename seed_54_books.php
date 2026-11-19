<?php
require 'c:/Users/Nasya/ecommerce/config/database.php';
require 'c:/Users/Nasya/.gemini/antigravity/brain/28bc7961-6280-4b07-ab35-2dfc7054ca0e/scratch/verify_54_books.php';

if (!$pdo) {
    die("Database not connected\n");
}

echo "Updating/Inserting 54 books into database...\n";

// Ensure categories 1..4 exist
$cats = [
    [1, 'Fiksi & Sastra', 'fiksi-sastra'],
    [2, 'Pengembangan Diri & Bisnis', 'pengembangan-diri-bisnis'],
    [3, 'Teknologi & Sains', 'teknologi-sains'],
    [4, 'Seni & Desain Kreatif', 'seni-desain-kreatif']
];
foreach ($cats as $c) {
    $stmt = $pdo->prepare("INSERT INTO categories (id, nama_kategori, slug) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nama_kategori = VALUES(nama_kategori)");
    $stmt->execute($c);
}

// Clear existing books safely if needed, or update existing 1..12 and insert the rest
// Let's use INSERT INTO books (id, category_id, judul, penulis, penerbit, harga, stok, deskripsi, cover_image) ON DUPLICATE KEY UPDATE
$stmt = $pdo->prepare("
    INSERT INTO books (id, category_id, judul, penulis, penerbit, harga, stok, deskripsi, cover_image) 
    VALUES (:id, :cid, :judul, :penulis, :penerbit, :harga, :stok, :deskripsi, :cover)
    ON DUPLICATE KEY UPDATE 
        category_id = VALUES(category_id),
        judul = VALUES(judul),
        penulis = VALUES(penulis),
        penerbit = VALUES(penerbit),
        harga = VALUES(harga),
        stok = VALUES(stok),
        deskripsi = VALUES(deskripsi),
        cover_image = VALUES(cover_image)
");

$id = 1;
foreach ($books as $b) {
    $stmt->execute([
        ':id' => $id,
        ':cid' => $b['category_id'],
        ':judul' => $b['judul'],
        ':penulis' => $b['penulis'],
        ':penerbit' => $b['penerbit'],
        ':harga' => $b['harga'],
        ':stok' => $b['stok'],
        ':deskripsi' => $b['deskripsi'],
        ':cover' => $b['cover_image'],
    ]);
    $id++;
}

$count = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
echo "Success! Total books in DB: $count\n";

