<?php
function fetchMenuData() {
    return json_decode(file_get_contents("http://tes-web.landa.id/intermediate/menu"), true);
}

function fetchTransaksiData($tahun) {
    return json_decode(file_get_contents("http://tes-web.landa.id/intermediate/transaksi?tahun=" . $tahun), true);
}

function initializeMenuData($menu) {
    $menuData = [];
    foreach ($menu as $menuItem) {
        $menuData[$menuItem['menu']] = [
            'menu' => $menuItem['menu'],
            'kategori' => $menuItem['kategori'],
            'value' => array_fill(0, 12, 0),
            'totalHarga' => 0,
        ];
    }
    return $menuData;
}

function calculateTotals($menuData, $transaksi) {
    $totalPerbulan = array_fill(0, 12, 0);
    $totalPertahun = 0;

    foreach ($transaksi as $transaction) {
        $harga = $transaction['total'];
        $tanggal = DateTime::createFromFormat("Y-m-d", $transaction['tanggal']);
        $bulan = $tanggal->format("n");
        $namaMenu = $transaction['menu'];

        if (isset($menuData[$namaMenu])) {
            $menuData[$namaMenu]['value'][$bulan - 1] += $harga;
            $menuData[$namaMenu]['totalHarga'] += $harga;
        }

        $totalPerbulan[$bulan - 1] += $harga;
        $totalPertahun += $harga;
    }

    return [$menuData, $totalPerbulan, $totalPertahun];
}

$availableYears = ['2021', '2022'];
$selectedYear = isset($_GET['tahun']) && in_array($_GET['tahun'], $availableYears) ? $_GET['tahun'] : null;

if ($selectedYear) {
    $menuData = initializeMenuData(fetchMenuData());
    list($menuData, $totalPerbulan, $totalPertahun) = calculateTotals($menuData, fetchTransaksiData($selectedYear));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabel Venturo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
        }
        td, th {
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="card" style="margin: 2rem 0rem;">
        <div class="card-header">
            Venturo - Laporan penjualan tahunan per menu
        </div>
        <div class="card-body">
            <form action="" method="get">
                <div class="row">
                    <div class="col-2">
                        <div class="form-group">
                            <select id="my-select" class="form-control" name="tahun">
                                <option value="">Pilih Tahun</option>
                                <?php foreach ($availableYears as $year): ?>
                                    <option value="<?= $year ?>" <?= $selectedYear === $year ? 'selected' : '' ?>><?= $year ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </div>
            </form>
            <hr>
            <?php if ($selectedYear): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered" style="margin: 0;">
                        <thead>
                        <tr class="table-dark">
                            <th rowspan="2" style="text-align:center;vertical-align: middle;width: 250px;">Menu</th>
                            <th colspan="12" style="text-align: center;">Periode Pada <?= $selectedYear ?></th>
                            <th rowspan="2" style="text-align:center;vertical-align: middle;width:75px">Total</th>
                        </tr>
                        <tr class="table-dark">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <th style="text-align: center;width: 75px;"><?= date("M", mktime(0, 0, 0, $i, 1, $selectedYear)) ?></th>
                            <?php endfor; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="table-secondary" colspan="14"><b>Makanan</b></td>
                        </tr>
                        <?php foreach ($menuData as $menu): ?>
                            <?php if ($menu['kategori'] === "makanan" || $menu['kategori'] === "minuman"): ?>
                                <tr>
                                    <td style="text-align: left;"><?= $menu['menu'] ?></td>
                                    <?php foreach ($menu['value'] as $value): ?>
                                        <td style="text-align: right;"><?= $value != 0 ? number_format($value) : "" ?></td>
                                    <?php endforeach; ?>
                                    <td style="text-align: right;"><b><?= number_format($menu['totalHarga']) ?></b></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <tr>
                            <td class="table-dark" colspan="1"><b>Total</b></td>
                            <?php foreach ($totalPerbulan as $total): ?>
                                <td class="table-dark" style="text-align: right;"><b><?= number_format($total) ?></b></td>
                            <?php endforeach; ?>
                            <td class="table-dark" style="text-align: right;" colspan="1"><b><?= number_format($totalPertahun) ?></b></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>