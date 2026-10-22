<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h2>Paket Konsultasi : <?= $konsultasi_header->row()->nm_paket; ?></h2>
    <table border="1" style="border: 1px solid #ccc;width: 100%;border-collapse: collapse;">
        <thead>
            <tr>
                <th align="center">#</th>
                <th align="left">Aktifitas</th>
                <th align="center">Harga</th>
                <th align="center">Bobot</th>
                <th align="center">Mandays</th>
            </tr>
        </thead>
        <tbody>
            <?php

            $ttl_harga = 0;
            $ttl_bobot = 0;
            $ttl_mandays = 0;

            if ($konsultasi_detail->num_rows() > 0) {
                $no = 1;
                foreach ($konsultasi_detail->result() as $dt) {
            ?>
                    <tr>
                        <td align="center" valign="top"><?php echo $no; ?></td>
                        <td align="left" valign="top" style='vertical-align:middle; width:340px;'>
                            <?php echo $dt->nm_aktifitas; ?>
                        </td>
                        <td align="center" valign="top">
                            <?php echo number_format($dt->harga_aktifitas); ?>
                        </td>
                        <td align="center" valign="top"><?php echo $dt->bobot; ?></td>
                        <td align="center" valign="top"><?php echo $dt->mandays; ?></td>
                    </tr>

                   <?php 

                    $ttl_harga += $dt->harga_aktifitas;
                    $ttl_bobot += $dt->bobot;
                    $ttl_mandays += $dt->mandays;
                    $no++;
                }
            } else {
                echo "
                    <tr>
                        <td colspan='5'><center>Belum ada aktifitas</center></td>
                    </tr>
                ";
            }
            ?>
        </tbody>
    </table>
</body>

<script>
    window.print();
</script>

</html>