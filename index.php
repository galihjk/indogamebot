<?php echo "<h1>hello world! ~@galihjk</h1>"; //--tes
die();
$folder = "data/tes/nama/";

include_once("helpers/hlp_data.php");

if(!empty($_GET['tesinsert'])){
    echo sdata_insert("tezt",[
        'name' => "name" . $_GET['tesinsert'],
        'address' => "address" . $_GET['tesinsert'],
        'we' => "we" . $_GET['tesinsert'],
        'om' => "om" . $_GET['tesinsert'],
    ]);
}
if(!empty($_GET['tesinsert2'])){
    echo sdata_insert("tezt",[
        'qwe' => "zxc" . $_GET['tesinsert2'],
    ]);
}
if(!empty($_GET['get'])){
    print_r( sdata_get_one("tezt",$_GET['get']));
    sdata_update("tezt",$_GET['get'],['updated'=>time()]);
    print_r( sdata_get_one("tezt",$_GET['get']));
}
if(!empty($_GET['tesfilter'])){
    echo "<pre>";
    print_r(sdata_find(
        "tezt",
        ['name'=>'nameGalih','qwe'=>'zxcAsd','we'=>'we'],
        100
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