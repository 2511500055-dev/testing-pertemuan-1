<?php
require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        echo "ini adalah metode GET";
        //GET ID
        if (isset($_GET['id'])) {
            $id = (int) ($_GET['id'] ?? 0);

            $stmt = mysqli_prepare($koneksi,
                     "SELECT 
                    m.id, 
                    m.nm_mahasiswa,
                    m.nim,
                    j.nm_jurusan AS jurusan
                FROM mahasiswa m 
                LEFT JOIN jurusan j 
                ON m.id_jurusan = j.id 
                WHERE m.id = ?");

            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (!$result) {
                gagalQuery($koneksi);
            }

            $data = mysqli_fetch_assoc($result);

            if (!$data) {
                sendResponse(
                    false,
                    "Data Mahasiswa tidak ditemukan",
                    null,
                    404
                );
            }

            sendResponse(true, "Data berhasil diambil", $data, 200);
        }

        //SEARCH
        if (isset($_GET['search'])) {

            $search = $_GET['search'] ?? "";
            // joker LIKE disambung di PHP, bukan ditempel di SQL
            $term = "%" . $search . "%";

            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT
                    m.id,
                    m.nm_mahasiswa,
                    m.nim,
                    j.nm_jurusan AS jurusan
                FROM mahasiswa m LEFT JOIN jurusan j
                ON m.id_jurusan = j.id
                WHERE m.nm_mahasiswa LIKE ? OR m.nim LIKE ?
                ORDER BY m.id DESC"
            );

            mysqli_stmt_bind_param($stmt, "ss", $term, $term);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);

            // ini kalau query gagal
            if (!$r)
                gagalQuery($koneksi);

            $data = mysqli_fetch_all($r, MYSQLI_ASSOC);

            if (empty($data)) {
                sendResponse(false, "Data Mahasiswa tidak ditemukan", null, 404);
            }

            sendResponse(true, "Data berhasil diambil", $data, 200);
        }

        //Page/Limit

        if (isset($_GET['page']) || isset($_GET['limit'])) {

            $page = isset($_GET['page'])
                ? (int)$_GET['page']
                : 1;

            $limit = isset($_GET['limit'])
                ? (int)$_GET['limit']
                : 10;

            if ($page < 1) {
                $page = 1;
            }

            if ($limit < 1) {
                $limit = 10;
            }

            $offset = ($page - 1) * $limit;

            $query = "SELECT 
                        m.id, 
                        m.nm_mahasiswa,
                        m.nim,
                        j.nm_jurusan AS jurusan
                    FROM mahasiswa m 
                    LEFT JOIN jurusan j 
                    ON m.id_jurusan = j.id 
                    ORDER BY m.id DESC
                    LIMIT $limit OFFSET $offset";

            $result = mysqli_query($koneksi, $query);

            if (!$result) {
                sendResponse(
                    false,
                    "Query gagal: " . mysqli_error($koneksi),
                    null,
                    500
                );
            }

            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            sendResponse(
                true,
                "Data berhasil diambil",
                $data,
                200
            );;
        }

        break;

    case 'POST':

        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || !isset($input['nm_mahasiswa']) || !isset($input['nim']) || !isset($input['id_jurusan'])) {
            sendResponse(false, "Data tidak lengkap", null, 400);
        }

        $nm_mahasiswa = $input['nm_mahasiswa'];
        $nim = $input['nim'];
        $id_jurusan = (int)$input['id_jurusan'];


        $check = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id= '$id_jurusan'");
        if (mysqli_num_rows($check) == 0) {
            sendResponse(false, "id_jurusan tidak ditemukan", null, 404);
        }

        $check_nim = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE nim = '$nim'");
        if (mysqli_num_rows($check_nim) > 0) {
            sendResponse(false, "NIM sudah digunakan", null, 409);
        }

        // insert data
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO mahasiswa (nm_mahasiswa, nim, id_jurusan, created_at)
             VALUES (?, ?, ?, CURDATE())"
        );

        mysqli_stmt_bind_param($stmt, "ssi", $nm_mahasiswa, $nim, $id_jurusan);

        if (mysqli_stmt_execute($stmt)) {
            sendResponse(true, "data tersimpan", ["id" => mysqli_insert_id($koneksi)], 201);
        } else {
            gagalQuery($koneksi);
        }

        echo "ini adalah metode POST";
        break;
    default:
        sendResponse(false, "Metode tidak diizinkan", null, 405);
        break;

    case 'PUT':

        $input = json_decode(file_get_contents("php://input"), true);
        #cek apakah nim, nama, dan id_jurusan ada di dalam body yang dikirim
        if (!$input || !isset($input['nm_mahasiswa']) || !isset($input['nim']) || !isset($input['id_jurusan'])) {
            sendResponse(false, "Data tidak lengkap", null, 400);
        }

        $id = $_GET['id'];

        $nama = $input['nm_mahasiswa'];
        $nim = $input['nim'];
        $id_jurusan = $input['id_jurusan'];

        #Cek Id Mahasiswa
        $cek_mahasiswa = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE id = '$id' ");
        if (mysqli_num_rows($cek_mahasiswa) == 0) {
            sendResponse(false, "Mahasiswa tidak ditemukan", null, 404);
        }

        #Cek apakah jurusan exist
        $cek_jurusan = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id = '$id_jurusan' ");
        if (mysqli_num_rows($cek_jurusan) == 0) {
            sendResponse(false, "Jurusan tidak ditemukan", null, 404);
        }

        #Cek penggunaan NIM
        $cek_nim = mysqli_query($koneksi, "SELECT nim FROM mahasiswa WHERE nim = '$nim' AND id = '$id'");
        if (mysqli_num_rows($cek_nim) > 0)
            sendResponse(false, "NIM sudah terdaftar", 409);

        #Update
        $query = "UPDATE mahasiswa SET nm_mahasiswa = '$nama', nim = '$nim', id_jurusan = '$id_jurusan' WHERE id = '$id'";

        if (mysqli_query($koneksi, $query)) {
            sendResponse(true, "Data Berhasil Diperbarui", ["id" => $id], 200);
        } else {
            sendResponse(false, mysqli_error($koneksi), null, 500);
        }
        break;


    case 'DELETE':

        $id = $_GET['id'];

        $cek_mahasiswa = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE id = '$id' ");
        if (mysqli_num_rows($cek_mahasiswa) == 0)
            sendResponse(false, "Mahasiswa tidak ditemukan", null, 404);

        $query = "DELETE FROM mahasiswa WHERE id = '$id'";

        if (mysqli_query($koneksi, $query)) {
            sendResponse(true, "Data Berhasil Dihapus", ["id" => $id], 200);
        } else {
            sendResponse(false, mysqli_error($koneksi), null, 500);
        }
        break;


    default:
        sendResponse(false, "Metode tidak diizinkan", null, 405);
        break;
}