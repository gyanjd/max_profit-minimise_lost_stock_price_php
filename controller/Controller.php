<?php

	include_once '../database/db.php';

    class Controller extends Connection
    {
        public function upload_csv(){

            $data = 'file_not_found';
            if(!empty($_FILES['file']['name'])){
                // Allowed mime types
                $csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');

                if(!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $csvMimes)){

                    if(is_uploaded_file($_FILES['file']['tmp_name'])){

                        $this->mysqli->query("DELETE FROM stock_price_list");
                        $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
                        fgetcsv($csvFile);

                        while (($line = fgetcsv($csvFile)) !== FALSE) {
                            $date = date('Y-m-d',strtotime($line[1]));
                            $stock_name = $line[2];
                            $price = $line[3];

                            $this->mysqli->query("INSERT INTO stock_price_list (cl_date, stock_name, price) VALUES ('".$date."', '".$stock_name."', '".$price."')");
                        }

                        fclose($csvFile);

                        $result = $this->mysqli->query("SELECT * FROM stock_price_list LIMIT 1");
                        if($result->num_rows > 0){
                            $data = 'success';
                        } else {
                            $data = 'invalid_file';
                        }

                    } else {
                        $data = 'error';
                    }
                } else {
                    $data = 'invalid_file';
                }
            }
            echo json_encode($data);
        }

        public function stock_list(){
            $error = 0;
            $message = '';
            $list = array();

            $sql = "SELECT DISTINCT stock_name, DATE_FORMAT(created_at, '%d/%m/%Y') as created_at FROM stock_price_list GROUP BY stock_name";
            // print $sql;
            $result = $this->mysqli->query($sql);

            if ($result->num_rows > 0) {

                while($row = $result->fetch_assoc()) {
                    array_push ($list, $row);
                }
                $data['items'] = $list;
               
            }else{
                $error++;
            } 

            $data['error'] = $error;
            $data['message'] = $message;
            echo json_encode($data);
        }

        public function stock_date(){
            $error = 0;
            $message = '';
            $list = array();

            $sql = "SELECT DATE_FORMAT(min(cl_date), '%d/%m/%Y') as min_date, DATE_FORMAT(max(cl_date), '%d/%m/%Y') as max_date from stock_price_list WHERE stock_name = '".$_GET['stock_name']."'";
            // print $sql;
            $result = $this->mysqli->query($sql);
            
            if ($result->num_rows > 0) {

                while($row = $result->fetch_assoc()) {
                    array_push ($list, $row);
                }
                $data['items'] = $list;
               
            }else{
                $error++;
            } 

            $data['error'] = $error;
            $data['message'] = $message;
            echo json_encode($data);
        }

        public function calculate_profit(){
            $error = 0;
            $message = '';
            $list = array();

            $res1 = explode("/", $_GET['stock_start_date']);
            $strtDate = $res1[2]."-".$res1[1]."-".$res1[0];

            $res2 = explode("/", $_GET['stock_end_date']);
            $endDate = $res2[2]."-".$res2[1]."-".$res2[0];

            $sql = "SELECT DATE_FORMAT(cl_date, '%d/%m/%Y') as cl_date, price from stock_price_list WHERE stock_name = '". $_GET['stock_name']."' AND cl_date BETWEEN '". $strtDate ."' AND '". $endDate ."' ORDER BY cl_date";

            $result = $this->mysqli->query($sql); 

            if ($result->num_rows >= 2) {

                while($row = $result->fetch_assoc()) {
                    array_push ($list, $row);
                }
            
                $data = $this->calc_profit($list);
            
            }else{
                $error++;
            } 

            $data['error'] = $error;
            $data['message'] = $message;
            echo json_encode($data);
        }

        private function calc_profit($list){

            $diff = PHP_INT_MIN;
            $max_so_far = $list[count($list)-1]['price'];
            $tot = $list[count($list)-1]['price'];
            $buyIndex;
            $sellIndex = count($list)-1;
            $isell = count($list)-1;

            for ($i = count($list)-2; $i >= 0; $i--)
            {   
                $tot += $list[$i]['price'];

                if ($list[$i]['price'] > $max_so_far){
                    if($diff < ($max_so_far - $list[$i]['price'])){
                        $diff = $max_so_far - $list[$i]['price'];
                        $buyIndex = $i;
                        if($i != 0){
                            $sellIndex = $isell;
                        }
                    }
                    $max_so_far = $list[$i]['price'];
                    $isell = $i;
                } else {
                    if($diff < ($max_so_far-$list[$i]['price'])){
                        $diff = $max_so_far-$list[$i]['price'];
                        $buyIndex = $i;
                        if($i != 0){
                            $sellIndex = $isell;
                        }
                    }
                }
            }

            $mean = $tot/count($list);

            $deviation = $this->calc_deviation($mean, $list);

            $data = [ 
                        "profit"     => $diff * 200, 
                        "buy_date"   => $list[$buyIndex]['cl_date'], 
                        "sell_date"  => $list[$sellIndex]['cl_date'],
                        "buy price"  => $list[$buyIndex]['price'],
                        "sell price" => $list[$sellIndex]['price'],
                        "mean"       => number_format((float)$mean, 2, '.', ''),
                        "deviation"  => number_format((float)$deviation, 2, '.', '')
                    ];

            return $data;
        }

        public function calc_deviation($mean, $list){
            $tot = 0;

            for($i = 0; $i < count($list); $i++){
                $tot += pow(($mean-$list[$i]['price']),2);
            }

            $var = $tot/count($list);
            return sqrt($var);
        }
    }

    $obj = new Controller();
    if (isset ($_POST['fn'])) { $fn =  $_POST['fn']; }
    if (isset ($_GET['fn'])) { $fn = $_GET['fn']; }

    if (isset ($fn)) { $obj->$fn (); }

?>