<?php

include_once 'includes/config.php';
include_once 'includes/multicategory.class.php';


if (isset($_GET['stage']) && $_GET['stage'] == 'open') {
    $_SESSION['open_' . $_GET['folder']] = true;
} elseif (isset($_GET['stage']) && $_GET['stage'] == 'close') {
    $_SESSION['open_' . $_GET['folder']] = false;
}

// Function to delete a tree from the database
function delete_tree($del_id){
    global $mysql, $curCfg, $imageTable, $imagePrimarykey, $videoTable, $videoPrimarykey, $treeByParent, $admin_imagefolder, $admin_videofolder;
    
    // Get delete info
    $deleteInfo = $mysql->getData($curCfg['table_name'], array($curCfg['table_primarykey'] => $del_id));
    
    // If there are children of the deleted item, delete them as well
    if(isset($treeByParent[$deleteInfo[$curCfg['table_primarykey']]]) && $treeByParent[$deleteInfo[$curCfg['table_primarykey']]]){
        foreach ($treeByParent[$deleteInfo[$curCfg['table_primarykey']]] as $child) {
            delete_tree($child[$curCfg['table_primarykey']]);
        }
    }
    
    // Loop through all fields to find images and videos to delete
    foreach ($curCfg['fields'] as $field) {
        if ($field['field_type'] == 'image') {
            if ($deleteInfo[$field['field_index']]) {
                $mysql->delete($imageTable, array($imagePrimarykey => $deleteInfo[$field['field_index']]));
                $folder = str_pad($deleteInfo[$field['field_index']], 11, "0", STD_PAD_LEFT);
                deleteFolder("$admin_imagefolder/$folder");
            }
        } else if ($field['field_type'] == 'video') {
            if ($deleteInfo[$field['field_index']]) {
                $mysql->delete($videoTable, array($videoPrimarykey => $deleteInfo[$field['field_index']]));
                $folder = str_pad($deleteInfo[$field['field_index']], 11, "0", STD_PAD_LEFT);
                deleteFolder("$admin_videofolder/$folder");
            }
        }
    }
    
    // Delete main data
    $mysql->delete($curCfg['table_name'], array($curCfg['table_primarykey'] => $del_id));
    
    // Re-order items if necessary
    if ($curCfg['table_order_field'] && $curCfg['table_order_type'] == 'order') {
        $parent_query = getParentQuery();
        $parent_query[$curCfg['table_parent_id']] = $deleteInfo[$curCfg['table_parent_id']];
        $res = $mysql->getList($curCfg['table_name'], $parent_query, '*', "$curCfg[table_order_field]", 'ASC');
        $ord = 1;
        foreach ($res as $info) {
            $mysql->update($curCfg['table_name'], array($curCfg['table_primarykey'] => $info[$curCfg['table_primarykey']]), array($curCfg['table_order_field'] => $ord));
            $ord++;
        }
    }
}

if (isset($_GET['stage']) && $_GET['stage'] == 'delete' && $_GET[$curCfg['table_primarykey']]) {
    $treeList = $mysql->getList($curCfg['table_name']);
    $treeByParent = array();
    foreach ($treeList as $data) {
        if (!isset($treeByParent[$data[$curCfg['table_parent_id']]])) {
            $treeByParent[$data[$curCfg['table_parent_id']]] = array();
        }
        $treeByParent[$data[$curCfg['table_parent_id']]][] = $data;
    }
    delete_tree($_GET[$curCfg['table_primarykey']]);
} else if (isset($_GET['stage']) && ($_GET['stage'] == 'order_up' || $_GET['stage'] == 'order_down')) {
    $query_data = array($curCfg['table_primarykey'] => $_GET[$curCfg['table_primarykey']]);
    // get delte info
    $orderInfo = $mysql->getData($curCfg['table_name'], $query_data);

    $ordid = $_GET[$curCfg['table_primarykey']];
    $oldOrder = $orderInfo[$curCfg['table_order_field']];
    if ($_GET['stage'] == 'order_up') {
        $newOrder = $oldOrder - 1;
    } else if ($_GET['stage'] == 'order_down') {
        $newOrder = $oldOrder + 1;
    }

    foreach ($_POST as $key => $value) {
        if (preg_match('/^ord_/i', $key) && is_numeric($_POST[$key])) {
            $ordid = str_replace('ord_', '', $key);
            $oldOrder = str_replace('ord_', '', $key);
            $newOrder = $_POST[$key];
        }
    }
    $parent_query = array();
    $parent_query[$curCfg['table_parent_id']] = $orderInfo[$curCfg['table_parent_id']];

    $res = $mysql->getList($curCfg['table_name'], $parent_query, '*', "$curCfg[table_order_field]", 'ASC');

    $data = array();

    foreach ($res as $info) {
        $data[] = $info[$curCfg['table_primarykey']];
        if ($ordid == $info[$curCfg['table_primarykey']]) {
            $oldOrder = $info[$curCfg['table_order_field']];
        }
    }
    list($sortdata) = array_splice($data, ($oldOrder - 1), 1);
    $newarray = array_splice($data, 0, $newOrder - 1);
    $newarray[] = $sortdata;
    foreach ($data as $a) {
        $newarray[] = $a;
    }
    $ord = 1;
    foreach ($newarray as $id) {
        $mysql->update($curCfg['table_name'], array($curCfg['table_primarykey'] => $id), array($curCfg['table_order_field'] => $ord));
        $ord++;
    }
} else if (isset($_GET['stage']) && $_GET['stage'] == 'active' && $_GET[$curCfg['table_primarykey']]) {
    $query_data = array($curCfg['table_primarykey'] => $_GET[$curCfg['table_primarykey']]);
    // get delte info
    $info = $mysql->getData($curCfg['table_name'], $query_data);
    $newdata = array(
        'status' => $info['status']?0:1
    );
    $mysql->update($curCfg['table_name'], array($curCfg['table_primarykey'] => $_GET[$curCfg['table_primarykey']]), $newdata);
}
if($curCfg['parent_related_key']){
    $query_data = array($curCfg['parent_related_key'] => $_GET[$curCfg['parent_related_key']]);
}
$res = $mysql->getList($curCfg['table_name'], $query_data, '*', $curCfg['table_order_field'], 'ASC');

ob_start();

?>  <div class="row">
        <div class="<?php echo $curCfg['listwidth_class'] ? $curCfg['listwidth_class'] : 'col-md-12'; ?>">
            <div class="x_panel">
                <div class="breadcrumb row">
                <?php foreach ($_SESSION['bp'] as $bp) { ?>
                    /<?php $json = json_decode($bp, true); echo $tbCfgs[$json['t']]['table_title']; ?>
                <?php } ?>
                /<?php echo $tbCfgs[$_GET['t']]['table_title']; ?>
                </div>
                <!--  list header start -->
                <?php include_once 'related_section.php'; ?>
                <div class="content">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box-header">
                                <h3 class="box-title">
                                    <?php if ($curCfg['oi']['allow_create']) { ?>
                                        <a href="<?php echo get_link('create'); ?>&default_field=<?php echo $tbCfgs[$curCfg['table_name']]['table_parent_id']; ?>&default_value=0"><span
                                                class="label label-success"><?php echo get_systext('list_create'); ?> <?php echo htmlspecialchars($curCfg['title']); ?></span></a>
                                    <?php } ?>
                                </h3>
                            </div>
                            <!-- /.box-header -->
                            <div class="box-body no-padding">
                                <?php
                                $catClass = new multiCat();
                                $catClass->indexKeyName = $curCfg['table_primarykey'];
                                //$catClass->indexfieldName = $curCfg['table_title_field'];
                                //$catClass->indexParentName = $tbCfgs[$curCfg['table_name']]['table_parent_id'];
                                $catClass->indexParentName = 'parent_id';
                                $catClass->extraLink = get_link();
                                echo $catClass->getTree($res, 0);
                                ?>
                            </div>
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                    <script type="text/javascript">
                        function set_status(id){
                            window.location = '?<?php echo get_link(); ?>&stage=active&<?php echo $curCfg['table_primarykey']; ?>='+id+'&st='+$(window).scrollTop();
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
                
<?php

if (isset($cms_content)) {
    $cms_content .= ob_get_contents();
} else {
    $cms_content = ob_get_contents();
}
ob_end_clean();

include 'template.php';
?>