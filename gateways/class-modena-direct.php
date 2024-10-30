<?php
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

class Modena_Direct_Payment extends Modena_Base_Payment {
  protected $service_info;
  protected $locality_EE;
  protected $locality_LV;
  protected $locality_LT;

	
  public function __construct() {
    $this->id = 'modena_direct';
    $this->enabled = $this->get_option('enabled');
    $this->method_title = __('Modena Direct', 'modena');
    $this->initialize_variables_with_translations($this->id);
    add_filter('woocommerce_get_order_item_totals', array($this, 'customize_payment_method_order_totals'), 10, 3);
    parent::__construct();
  }

    public function get_description() {
        $options = [];
        try {
            $options = $this->modena->getPaymentOptions();

        } catch (Exception $e) {
            $this->logger->error(sprintf("Error retrieving payment options: %s", $e->getMessage()));
            $this->logger->error($e->getTraceAsString());
        }
		
		$defaultBICValue = $this->get_option('environment') == "sandbox" ? "SANDEE2X" : "HABAEE2X";

        // Dropdown for selecting locality
        $description = '<select class="modena_direct_dropdown_style" id="locality_selector" onchange="toggleLocality()">';
        $description .= '<option value="EE">' . $this->locality_EE . '</option>';
        $description .= '<option value="LV">' . $this->locality_LV . '</option>';
        $description .= '<option value="LT">' . $this->locality_LT . '</option>';
		$description .= '</select>';

        // Start building the list of payment options
        $description .= '<ul id="mdn_banklinks_wrapper" class="mdn_banklinks" style="margin: 0 14px 24px 14px !important; list-style-type: none;">';
        foreach (['EE', 'LV', 'LT'] as $locality) {
            foreach ($options[$locality]['paymentMethods'] as $i => $option) {
                $id = sprintf('%s_%s_%d', $locality, str_replace(' ', '_', strtoupper($option['name'])), $i);
                $src = $option['buttonUrl'];
                $value = $option['code'];
                $alt = $option['name'];
                $class = 'mdn_banklink_img' . ($i === 0 && $locality === 'EE' ? ' mdn_checked' : '');
                $style = $locality === 'EE' ? '' : 'style="display:none;"'; // Hide non-Estonian options by default
                $description .= sprintf("<li class=\"locality_%s\" %s><img id=\"mdn_bl_option_%s\" src=\"%s\" alt=\"%s\" class=\"%s\" code=\"%s\" onclick=\"selectModenaBanklink('%s', '%s')\"/></li>", $locality, $style, $id, $src, $alt, $class, $value, $id, $value);
            }
        }
        $description .= '</ul>';
        $description .= '<input type="hidden" id="modena_selected_payment_method" name="modena_selected_payment_method" value="'.$defaultBICValue.'">';
		$description .= '<input type="hidden" id="modena_selected_locality" name="modena_selected_locality" value="EE">';

        Modena_Load_Checkout_Assets::getInstance();

        return "{$description}{$this->getServiceInfoHtml()}";
    }

  private function getServiceInfoHtml() {
    $linkLabel = $this->service_info;

    return "<a class='mdn_service_info' href='https://modena.ee/makseteenused/' target='_blank'>{$linkLabel}</a>";
  }

  public function get_icon_alt() {
    return $this->default_alt;
  }

  public function get_icon_title() {
    return $this->default_icon_title_text;
  }

  protected function postPaymentOrderInternal($request) {
    return $this->modena->postDirectPaymentOrder($request);
  }

  protected function getPaymentApplicationStatus($applicationId) {
    return $this->modena->getDirectPaymentApplicationStatus($applicationId);
  }

  function customize_payment_method_order_totals($total_rows, $order, $tax_display) {
    foreach ($total_rows as $key => $total) {
      if ($key == 'payment_method') {
        $payment_method_id = $order->get_payment_method();
        if ($payment_method_id === 'modena_direct') {
          $total_rows[$key]['value'] = $total['value'] . ' (' . $order->get_meta(self::MODENA_SELECTED_METHOD_KEY) . ')';
        }
      }
    }

    return $total_rows;
  }
}