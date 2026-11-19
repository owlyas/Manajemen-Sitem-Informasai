<?php
/**
 * Bloom&Books - Update Book Cover Images to Real Covers
 * Uses Open Library Covers API for international books,
 * and curated thematic URLs for Indonesian books.
 *
 * Run this script once: http://localhost/ecommerce/update_covers.php
 */
require __DIR__ . '/config/database.php';

// Real book cover URLs mapped by book ID
$covers = [
    // 1: Laut Bercerita - Leila S. Chudori (ISBN: 9786024246372)
    1  => 'https://covers.openlibrary.org/b/isbn/9786024246372-L.jpg',

    // 2: Bumi Manusia - Pramoedya Ananta Toer (ISBN: 9789799731234)
    2  => 'https://covers.openlibrary.org/b/isbn/9789799731234-L.jpg',

    // 3: Hujan - Tere Liye (ISBN: 9786020324784)
    3  => 'https://covers.openlibrary.org/b/isbn/9786020324784-L.jpg',

    // 4: Filosofi Teras - Henry Manampiring (ISBN: 9786020392660)
    4  => 'https://covers.openlibrary.org/b/isbn/9786020392660-L.jpg',

    // 5: Atomic Habits - James Clear (ISBN: 9780735211292)
    5  => 'https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg',

    // 6: The Psychology of Money - Morgan Housel (ISBN: 9780857197689)
    6  => 'https://covers.openlibrary.org/b/isbn/9780857197689-L.jpg',

    // 7: Clean Code - Robert C. Martin (ISBN: 9780132350884)
    7  => 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg',

    // 8: The Pragmatic Programmer - David Thomas & Andrew Hunt (ISBN: 9780135957059)
    8  => 'https://covers.openlibrary.org/b/isbn/9780135957059-L.jpg',

    // 9: Sapiens - Yuval Noah Harari (ISBN: 9780062316097)
    9  => 'https://covers.openlibrary.org/b/isbn/9780062316097-L.jpg',

    // 10: The Design of Everyday Things - Don Norman (ISBN: 9780465050659)
    10 => 'https://covers.openlibrary.org/b/isbn/9780465050659-L.jpg',

    // 11: Steal Like an Artist - Austin Kleon (ISBN: 9780761169253)
    11 => 'https://covers.openlibrary.org/b/isbn/9780761169253-L.jpg',

    // 12: Cantik Itu Luka - Eka Kurniawan (ISBN: 9786020323381)
    12 => 'https://covers.openlibrary.org/b/isbn/9786020323381-L.jpg',
];

echo "<pre>";
echo "=== Bloom&Books - Update Book Cover Images ===\n\n";

if ($pdo === null) {
    echo "❌ Database tidak terhubung. Pastikan MySQL/XAMPP berjalan.\n";
    exit;
}

$successCount = 0;
$failCount = 0;

foreach ($covers as $id => $url) {
    try {
        $stmt = $pdo->prepare("UPDATE books SET cover_image = ? WHERE id = ?");
        $result = $stmt->execute([$url, $id]);
        if ($result && $stmt->rowCount() > 0) {
            echo "✅ Book ID $id updated → $url\n";
            $successCount++;
        } else {
            echo "⚠️  Book ID $id — tidak ditemukan atau tidak berubah.\n";
            $failCount++;
        }
    } catch (PDOException $e) {
        echo "❌ Book ID $id — Error: " . $e->getMessage() . "\n";
        $failCount++;
    }
}

echo "\n=== Selesai! $successCount berhasil, $failCount gagal/tidak ditemukan. ===\n";
echo "</pre>";
