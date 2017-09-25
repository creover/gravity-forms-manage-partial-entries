<?php

class Continue_Partial_Entries {
    private static $initiated = false;
    private static $partial_entry_id = 'pending';
    
    public static function init() {
        if ( ! self::$initiated ) {
            self::init_hooks();
        }
    }
    
    public static function init_hooks() {
        self::$initiated = true;

        // time for hooks
        add_filter( 'gform_pre_render',             array( __class__, 'prepopulate_partial_entry' ), 11, 1 );
        add_filter( 'gform_form_settings',          array( __class__, 'continue_pe_settings' ), 50, 2 );
        add_filter( 'gform_pre_form_settings_save', array( __class__, 'continue_pe_save_form_settings' ) );
        add_filter( 'gform_tooltips',               array( __class__, 'continue_pe_add_tooltips' ) );
        add_filter( 'gform_form_tag',               array( __class__, 'filter_gform_pe_tag' ), 11, 2 );
        add_action( 'gform_after_submission',       array( __class__, 'clear_pe_after_submit', 10, 2 ) );
        
    }

    //Add old entry values to form
    public static function prepopulate_partial_entry( $form ) {
        
        // safeguard for when this filter is called via a Partial Entries AJAX call
        if( ! class_exists( 'GFFormDisplay' ) ) {
            return $form;
        }
        
        //Check if this is an applicable form
        if( ! self::is_applicable_form( $form ) ) {
            return $form;
        }
        
        if ( class_exists( 'Duplicate_Entry' ) ) {
            if($form['is_entry_copy']) {return $form;}           
        }
        
        $form_id = $form["id"];
        $is_first_load = isset( GFFormDisplay::$submission[ $form_id ] ) ? false : true;
        
        //Make sure this only runs on the initial form load
        if ( ! $is_first_load ){
            return $form;
        }
        
        //Check to see if a partial entry exists
        $partial_entry = self::is_partial_entry($form_id);        
        if( ! empty($partial_entry) ){
            //retrieve the entry object
            $entry = GFAPI::get_entry( $partial_entry );
            $form = self::set_field_values( $form, $entry );
            self::$partial_entry_id = rgar( $entry, 'partial_entry_id' );        
        }

        return $form;
    
    }
    
    public static function set_field_values( $form, $entry, $exclude = array() ) {
        //Input all form data from entry into the fields in the form of default values
        foreach ( $form['fields'] as &$field ) {
            $input_type = $field->get_input_type();
            if ( $input_type == 'checkbox' ) {
                for ( $i = 0, $count = sizeof( $field->inputs ); $i < $count; $i ++ ) {
                    $input  = $field->inputs[ $i ];
                    $choice = $field->choices[ $i ];
                    $field_val = rgar( $entry, $input['id'] );
                    if ( ! empty( $field_val ) ) {
                        $choice['isSelected'] = true;
                    } else {
                        $choice['isSelected'] = false;
                    }
                    $field->choices[ $i ] = $choice;
                }
            } elseif ( $input_type == 'time' ) {
                $field_val = rgar( $entry, $field->id );
                if ( ! empty( $field_val ) && preg_match( '/^(\d*):(\d*) ?(.*)$/', $field_val, $matches ) ){
                    $i = 1;
                    foreach ( $field->inputs as &$input ) {
                        $input['defaultValue'] = $matches[$i];
                        $i++;
                    }
                }
            } elseif ( is_array( $field->inputs ) ) {
                foreach ( $field->inputs as &$input ) {
                    $input['defaultValue'] = rgar( $entry, $input['id'] );
                }
            } else {
                $field->defaultValue = rgar( $entry, $field->id );
            }
        }
        
        return $form;
    }
    
    public static function filter_gform_pe_tag( $form_tag, $form ) {
        $form_tag = str_replace( "pending", self::$partial_entry_id, $form_tag );
        return $form_tag;
    }
    
    public static function is_applicable_form( $form ) {

        $continue_partial = isset( $form['continue_partial'] ) ? $form['continue_partial'] : false;

        return $continue_partial;
    }
    
    public static function is_partial_entry( $form_id ) {
        $sorting         = array( 'key' => 'date_created', 'direction' => 'DESC' );
        $search_criteria = array(
            'status'        => 'active',
            'field_filters' => array(
                array(
                    'key'   => 'created_by',
                    'value' => get_current_user_id()
                ),
                array( 
                    'key' => 'partial_entry_id',
                    'operator' => 'isnot',
                    'value' => '' 
                ),
            ),
        );

        $entries = GFAPI::get_entries( $form_id, $search_criteria, $sorting );
        
        if ( ! is_array($entries) ) { return false;}
        
        return rgar( $entries[0], 'id' );
    }
    
    //Add manage partial entry settings to the form settings
    public static function continue_pe_settings( $form_settings, $form ) {

        // create settings on position 50 (right after Admin Label)
        $tr_manage_partial = '
            <tr>
                <td colspan="2"><h4 class="gf_settings_subgroup_title">Manage Partial Entries</h4></td>
            </tr>
            <tr>
                <th>Continue Partial Entries ' . gform_tooltip( 'continue_partial', '', true ) . ' </th>
                <td>
                    <input type="checkbox" name="continue_partial" id="continue_partial" ' . checked( rgar( $form, "continue_partial" ), '1', false ) . '" value="1" />
                    <label for="continue_partial">Enable Continue Partial Entries</label>
                </td>
            </tr>';


        $form_settings["Form Options"]["manage_partial"] = $tr_manage_partial;
        return $form_settings;
    }
    
    public static function continue_pe_save_form_settings( $form ) {

        //update settings
        $form['continue_partial'] = rgpost( 'continue_partial' );

        return $form;

    }
    
    // Filter to add a new tooltip
    public static function continue_pe_add_tooltips( $tooltips ) {
        $tooltips["continue_partial"]                 = "<h6>Continue Partial Entries</h6>This form will automatically continue partial entries where they were left off";

        return $tooltips;
    }
    
    /**
    * Clear Partial Entries
    * Used to clear out the partial entries after form submission
    *
    */
    public static function clear_pe_after_submit( $entry, $form ) {
        //Check if this is an applicable form
        if( ! self::is_applicable_form( $form ) ) {
            return;
        }       
        $form_id = $form["id"];   
        self::clear_partial_entries( $form_id );
    }
    
    public static function clear_partial_entries( $form_id ) {       
        $form_id = intval( $form_id );
        $search_criteria = array(
            'status'        => 'active',
            'field_filters' => array(
                array(
                    'key'   => 'created_by',
                    'value' => get_current_user_id()
                ),
                array( 
                    'key' => 'partial_entry_id',
                    'operator' => 'isnot',
                    'value' => '' 
                ),
            ),
        );

        $entries = GFAPI::get_entries( $form_id, $search_criteria );
    
        foreach ( $entries as $single){
            $entry_id = rgar( $single, 'id' );
            $result = GFAPI::update_entry_property( $entry_id, 'status', 'trash' );
        }       
    }
}