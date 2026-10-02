<?php

require_once "../config.php";
require_once "../helpers/response.php";

$q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j on m.jurusan_id = j.id ORDER BY m.id DESC";

$r = mysqli_query($koneksi, $q);

if(!$r) sendResponse(false, "query gagal: " . mysqli_error($koneksi), null, 500);

$data = [];
while ($row = mysqli_fetch_assoc($r)) {
    $data[] = $row;
}

sendResponse(true, "berhasil", $data, 200);

// mahasiswa.php?nim=12
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $q = "SELECT 

            m.id,
            m.nama,
            m.nim,
            j.nama_jurusan AS jurusan
          FROM mahasiswa m
          LEFT JOIN jurusan j 
          ON m.jurusan_id = j.id
          WHERE m.id = '$id'";
          ORDER BY m.id DESC";

    $r = mysqli_query($koneksi, $q);

    if (!$r) {
        sendResponse(
            false,
            "query gagal: " . mysqli_error($koneksi),
            null,
            500
        );
    }

    $data = mysqli_fetch_assoc($r);

    if (!$data) {
        sendResponse(false, "mahasiswa tidak ditemukan", null, 404);
    }

    sendResponse(true, "berhasil", $data, 200);
}

// Pagination
// mahasiswa.php?limit=-2
// mahasiswa.php?page=-150
// mahasiswa.php?page=1&1limit=2
if (isset($_GET['page']) || isset($_GET['limit'])) {

    $page = isset($_GET['page']) 
        ? (int) $_GET['page'] 
        : 1;

    $limit = isset($_GET['limit']) 
        ? (int) $_GET['limit'] 
        : 10;

    if ($page < 1) {
        $page = 1;
    }

    if ($limit < 1) {
        $limit = 10;
    }

    $offset = ($page - 1) * $limit;

    $q = "SELECT 
            m.id,
            m.nama,
            m.nim,
            j.nama_jurusan AS jurusan
          FROM mahasiswa m
          LEFT JOIN jurusan j
            ON m.jurusan_id = j.id
          ORDER BY m.id DESC
          LIMIT $limit OFFSET $offset";

    $r = mysqli_query($koneksi, $q);

    if (!$r) {
        sendResponse(
            false,
            "query gagal: " . mysqli_error($koneksi),
            null,
            500
        );
    }

    $data = [];

    while ($row = mysqli_fetch_assoc($r)) {
        $data[] = $row;
    }

    sendResponse(
        true,
        "berhasil",
        $data,
        200
    );

}

// get ALL