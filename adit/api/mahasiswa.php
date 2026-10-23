<?php

require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        //url: [GET] /mahasiswa.php?id=[id]
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
                WHERE m.id = '$id'
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
        break;

    case 'POST':
        // baru hari ini: create + validasi
        $input = json_decode(file_get_contents("php://input"), true);

        // cek apakah input ada dan field nama, nim, jurusan_id ada
        if (!isset($input['nama']) || !isset($input['nim']) || !isset($input['jurusan_id'])) {
            sendResponse(false, "field nama, nim, jurusan_id wajib", null, 400);
        }
        $nama = $input['nama'];
        $nim = $input['nim'];
        $jurusan_id = (int) $input['jurusan_id'];

        // cek jurusan apakah ada
        $cek = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id='$jurusan_id'");
        if (mysqli_num_rows($cek) == 0) {
            sendResponse(false, "jurusan_id tidak ditemukan", null, 404);
        }

        // insert data mahasiswa
        $q = "INSERT INTO mahasiswa (nama,nim,jurusan_id,created_at) VALUES ('$nama','$nim','$jurusan_id',CURDATE())";
        if (mysqli_query($koneksi, $q)) {
            sendResponse(true, "data tersimpan", ["id" => mysqli_insert_id($koneksi)], 201);
        } else {
            sendResponse(false, mysqli_error($koneksi), null, 500);
        }

        echo $jurusan_id; die;
        break;

    case 'PUT':
        // Pastikan parameter id tersedia di URL
        if (!isset($_GET['id'])) {
            sendResponse(false, "id wajib diisi", null, 400);
        }
        $id = (int) $_GET['id'];

        $input = json_decode(file_get_contents("php://input"), true);

        if (!isset($input['nama']) || !isset($input['nim']) || !isset($input['jurusan_id'])) {
            sendResponse(false, "field nama, nim, jurusan_id wajib", null, 400);
        }
        $nama = $input['nama'];
        $nim = $input['nim'];
        $jurusan_id = (int) $input['jurusan_id'];

        // cek jurusan apakah ada
        $cek = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id='$jurusan_id'");
        if (mysqli_num_rows($cek) == 0) {
            sendResponse(false, "jurusan_id tidak ditemukan", null, 404);
        }

        $q = "UPDATE mahasiswa SET nama='$nama', nim='$nim', jurusan_id='$jurusan_id' WHERE id='$id'";
        if (mysqli_query($koneksi, $q)) {
            if (mysqli_affected_rows($koneksi) > 0) {
                sendResponse(true, "berhasil update data", null, 200);
            } else {
                sendResponse(false, "id tidak ditemukan atau tidak ada perubahan", null, 404);
            }
        } else {
            sendResponse(false, mysqli_error($koneksi), null, 500);
        }
        break;

    case 'DELETE':
        // Pastikan parameter id tersedia di URL
        if (!isset($_GET['id'])) {
            sendResponse(false, "id wajib diisi", null, 400);
        }
        $id = (int) $_GET['id'];

        $q = "DELETE FROM mahasiswa WHERE id='$id'";
        if (mysqli_query($koneksi, $q)) {
            if (mysqli_affected_rows($koneksi) > 0) {
                sendResponse(true, "berhasil hapus data", null, 200);
            } else {
                sendResponse(false, "id target tidak ditemukan", null, 404);
            }
        } else {
            sendResponse(false, mysqli_error($koneksi), null, 500);
        }
        break;

    default:
        // PUT / DELETE / Lainnya -> 405
        sendResponse(false, "Method not allowed", null, 405);
        break;
}