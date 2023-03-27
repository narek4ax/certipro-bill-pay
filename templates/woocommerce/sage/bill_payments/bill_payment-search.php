<?php
$e_class = '';
if( isset( $_GET['bp_action'] ) )
    $e_class = 'scroll_to_me';

?>
<div id="search-invoices" class="elink-forms <?php echo $e_class; ?>">

    <?php
    $search_fields = array(
        'bp_date_from' => array(
            'name' => 'Date From',
            'type' => 'text',
            'autocomplete'=>'off'
        ),
        'bp_date_to' => array(
            'name' => 'Date To',
            'type' => 'text',
            'autocomplete'=>'off'
        ),
        'bp_confirmation' => array(
            'name' => 'Confirmation',
            'type' => 'text',
            'autocomplete'=>'on'
        ),
        'bp_reference' => array(
            'name' => 'Reference',
            'type' => 'text',
            'autocomplete'=>'on'
        ),
        'bp_deposit' => array(
            'name' => 'Deposit',
            'type' => 'text',
            'autocomplete'=>'on'
        ),
        'bp_invoice' => array(
            'name' => 'Invoice',
            'type' => 'text',
            'autocomplete'=>'on'
        )
    );
    ?>
    <form action="" id="bp-search-form">
        <input type="hidden" name="bp_action" value="search">
        <?php
        $counter = 1;
        foreach ($search_fields as $sf_key => $search_field) {
            if($counter !==2){
                echo '<div>';
            }
            $s_val = ( isset($_GET[$sf_key]) )?$_GET[$sf_key]:'';
            echo '<input autocomplete ="'.$search_field['autocomplete'].'" type="' . $search_field['type'] . '" id="' . $sf_key . '" value="'.$s_val.'" name="' . $sf_key . '" placeholder="' . $search_field['name'] . '">';
            if($counter !==1){
                echo '</div>';
            }
            $counter++;
        }
        ?>
        <div class="bp-search">
            <input type="submit" id="sort_bp_order" value="Search">
        </div>
    </form>
</div>