<?php echo "hello world"; //--tes

$folder = "data/tes/nama/";

include_once("helpers/hlp_data.php");

if(!empty($_GET['tesinsert'])){
    echo data_insert("tezt",[
        'name' => "name" . $_GET['tesinsert'],
        'address' => "address" . $_GET['tesinsert'],
    ]);
}
if(!empty($_GET['tesinsert2'])){
    echo data_insert("tezt",[
        'qwe' => "zxc" . $_GET['tesinsert2'],
    ]);
}
if(!empty($_GET['get'])){
    print_r( data_get_one("tezt",$_GET['get']));
    data_update("tezt",$_GET['get'],['updated'=>time()]);
    print_r( data_get_one("tezt",$_GET['get']));
}
if(!empty($_GET['tesfilter'])){
    echo "<pre>";
    print_r(data_find(
        "tezt",
        ['name'=>'nameGalih','qwe'=>'zxcAsd'],
        1
    ));
}

/*
    if(file_exists($folder)){
        if(!is_readable($folder)){
            $id = 1;
            echo "not readable";
        }
        else{
            $scandir = scandir($folder);
            sort($scandir);
            $scandircount = count($scandir);
            echo "[scandircount$scandircount]<pre>";
            print_r($scandir);
            if($scandircount > 2){
                $id = $scandir[$scandircount-1] + 1;
            }
            else{
                $id = 1;
            }
        }
        echo "NEW ID=$id";
    }
    else{
        mkdir($folder, 0777, true);
        echo "DIBUAT";
    }
*/