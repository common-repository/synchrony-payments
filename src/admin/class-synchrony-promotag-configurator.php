<?php
/**
 * Tag Configurator Admin
 *
 * @package Synchrony\Payments\Admin
 */

namespace Synchrony\Payments\Admin;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Gateway\Synchrony_Client;
use Synchrony\Payments\Frontend\Synchrony_Promotag_Config;

/**
 * Class Synchrony_Promotag_Configurator
 */
class Synchrony_Promotag_Configurator {

	/**
	 * POST_TYPE
	 *
	 * @var string
	 */
	public const POST_TYPE = 'product';

	/**
	 * RULESET_MATCH_ANY
	 *
	 * @var string
	 */
	const RULESET_MATCH_ANY = 'ANY';

	/**
	 * RULESET_MATCH_ALL
	 *
	 * @var string
	 */
	const RULESET_MATCH_ALL = 'ALL';

	/**
	 * DATE_TIME_FORMAT
	 *
	 * @var string
	 */
	const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Config Helper
	 *
	 * @var Synchrony_Config_Helper $config_helper
	 */
	private $config_helper;

	/**
	 * Setting Config Helper
	 *
	 * @var Synchrony_Setting_Config_Helper $config_helper
	 */
	private $setting_config_helper;

	/**
	 * Constructor of this class called
	 *
	 * @return void
	 */
	public function __construct() {
		$this->config_helper         = new Synchrony_Config_Helper();
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
	}

	/**
	 * Get the all attributes.
	 *
	 * @return array
	 */
	public function retrieve_all_attributes() {
		$attribute_data = array();

		$args              = array(
			'post_type' => self::POST_TYPE,
			'limit'     => 1,
		);
		$exclude_attribute = $this->retrieve_exclude_attribute();
		foreach ( wc_get_products( $args ) as $product ) {
			$product_data = $product->get_data();
			foreach ( $product_data as $key => $value ) {
				if ( in_array( $key, $exclude_attribute, true ) ) {
					continue;
				}
				$label            = str_replace( '_', ' ', $key );
				$label_text       = ucwords( $label );
				$attribute_data[] = array(
					'value' => $key,
					'label' => $label_text,
				);
			}
			// GET WOOCOMMERCE CUSTOM ATTRIBUTES.
			foreach ( wc_get_attribute_taxonomies() as $attribute ) {
				if ( in_array( $attribute->attribute_name, $exclude_attribute, true ) ) {
					continue;
				}
				$attribute_data[] = array(
					'value' => $attribute->attribute_name,
					'label' => $attribute->attribute_label,
				);
			}
		}
		return $attribute_data;
	}

	/**
	 * Get all excluded attributes
	 *
	 * @return array
	 */
	private function retrieve_exclude_attribute() {
		$client            = new Synchrony_Client();
		$get_rules         = $client->retrieve_rulesets();
		$exclude_attribute = array();
		if ( isset( $get_rules['rulesets'] ) && isset( $get_rules['platformAttributes'] ) ) {
			$platform_rules_set = $get_rules['platformAttributes'];
			$custom_rules_set   = $get_rules['customAttributes'];
			$combined_rules_set = array_merge( $platform_rules_set, $custom_rules_set );
			foreach ( $combined_rules_set as $rules_setcustom ) {
				$exclude_attribute[] = $rules_setcustom['key'];
			}
			$exclude_attribute = array_unique( $exclude_attribute );
		}
		return $exclude_attribute;
	}

	/**
	 * Get all synchrony promotions from admin.
	 *
	 * @return array
	 */
	public function retrieve_synchrony_promotions() {
		$config_helper_obj    = new Synchrony_Config_Helper();
		$common_config_helper = new Synchrony_Common_Config_Helper();
		if ( ! $this->setting_config_helper->fetch_tag_rules() ) {
			?>
			<img src="<?php echo esc_url( $common_config_helper->fetch_synchrony_logo() ); ?>" alt="Synchrony Promotions:" />
			<p>Connnect with Synchrony to enable this feature.</p>
			<script type="text/javascript">
				jQuery(function ($) {
					$('button[name="save"]').hide();
				});
			</script>
			<?php
			return array();
		}
		$client      = new Synchrony_Client();
		$auth_result = '';
		if ( ! empty( $client->retrieve_auth_token() ) ) {
			$auth_result = json_decode( $client->retrieve_auth_token() );
		}
		$access_token           = ( $auth_result ) ? $auth_result->access_token : '';
		$is_synchrony_test_mode = $common_config_helper->does_test_mode();
		$promo_endpoint         = $config_helper_obj->fetch_api_endpoint( 'synchrony_test_promo_tag_determination_endpoint', 'synchrony_deployed_promo_tag_determination_endpoint', $is_synchrony_test_mode );
		$data                   = array(
			'attributes'           => $this->retrieve_all_attributes(),
			'partner_id'           => $this->setting_config_helper->fetch_partner_id(),
			'platform'             => $common_config_helper->fetch_platform(),
			'access_token'         => $access_token,
			'Tag_Rule'             => 'Y',
			'TAG_CONFIGURATOR_API' => $promo_endpoint,
			'attribute_value'      => $this->retrieve_all_attributes_value(),
		);
		$is_synchrony_test_mode = $common_config_helper->does_test_mode();
		$get_iframe_url         = $config_helper_obj->fetch_api_endpoint( 'synchrony_test_promo_tag_endpoint', 'synchrony_deployed_promo_tag_endpoint', $is_synchrony_test_mode );
		$form_fields            = array();
		?>
		<?php
		if ( ! $this->setting_config_helper->fetch_partner_id() ) {
			?>
			<div class="notice notice-warning my-acf-notice is-dismissible">
				<p>
			<?php
			esc_html_e( 'Please enter the Synchrony Partner Id, Client Id and Client Secret.', 'synchrony-payments' );
			?>
				</p>
			</div>
			<?php
		}
		?>
		<h2 style="float:left">Synchrony Promotion:</h2>
		<button type="button" id="syf_clear_cache" style="float:right; color: rgb(255, 255, 255); font-weight: 700; background-color: rgb(52, 101, 127); cursor:pointer;border: 2px solid transparent; border-radius: 6px; padding: 12px;">Clear Cache Tags</button>
		<p id="syf_cache_message" style="color:green; display:none; margin-right: 10px; float:right;">Cache Cleared Successfully.</p>
		<p id="syf_cache_message_error" style="color:red; display:none; margin-right: 10px; float:right;">Cache Clear Error.</p>
		<iframe src="<?php echo esc_url( $get_iframe_url ); ?>" style="border:none; margin: 0;" width="100%" height="800px"
			id="promotagsiframe" title="Synchrony Promotions" onload="setPromoTagParams()"></iframe>
		<script type="text/javascript">
			function setPromoTagParams() {
				document.getElementById('promotagsiframe').contentWindow.postMessage('<?php echo wp_json_encode( $data ); ?>', '<?php echo esc_url( $get_iframe_url ); ?>');
			}
			jQuery(function ($) {
				$("#syf_clear_cache").click(function(e) {
					e.preventDefault();
					var button = $(this);
					button.prop('disabled',true);
					button.css('background-color','grey');
					$('#syf_cache_message').hide();
					$('#syf_cache_message_error').hide();
					var syf_nce = '<?php echo esc_html( wp_create_nonce( 'syf_transient_nonce' ) ); ?>';
					var isadmin_value = '<?php echo esc_html( current_user_can( 'manage_options' ) ); ?>';
					$.ajax({
						type: "POST",
						url: "<?php echo esc_html( home_url() ) . '/wp-json/syf/v1/delete_transient/syf_tag'; ?>",
						data: {
							isadmin: isadmin_value,
						},
						beforeSend: function(xhr) {
							xhr.setRequestHeader('X-Syf-Nonce', syf_nce);
						},	
						success: function(result) {
							$('#syf_cache_message').show();
							button.prop('disabled',false);
							button.css('background-color','rgb(52, 101, 127)');
						},
						error: function(result) {
							$('#syf_cache_message_error').show();
							button.prop('disabled',false);
							button.css('background-color','rgb(52, 101, 127)');
						}
					});
				});
				$('button[name="save"]').hide();
			});
		</script>
		<?php
		return $form_fields;
	}

	/**
	 * TO GET PRODUCT DATA
	 *
	 * @param int $product_id This is product id.
	 * @return array|null
	 */
	public function retrieve_product_data( $product_id = '' ) {
		if ( 1 !== $this->setting_config_helper->fetch_tag_rules() ) {
			return array();
		}
		$client     = new Synchrony_Client();
		$tag_config = new Synchrony_Promotag_Config();
		$product    = wc_get_product();
		if ( '' === $product_id ) {
			$product_id = $product ? $product->get_id() : 0;
		} else {
			$product = wc_get_product( $product_id );
		}
		$productdata    = array();
		$products       = array();
		$tag_attributes = $client->retrieve_product_attributes();
		$data           = null;
		if ( ! empty( $tag_attributes ) && count( $tag_attributes ) > 0 ) {
			foreach ( $tag_attributes as $att_code ) {
				$att_code_value = null;
				$att_code_value = $tag_config->retrieve_product_attribute_label_by_value( $att_code, $product );
				if ( ! empty( $att_code_value ) ) {
					$productdata[ $att_code ] = $att_code_value;
				}
			}
			$productdata['item_price'] = $this->setting_config_helper->format_amount( $product->get_price() );
			$productdata['item_name']  = $product->get_title();
			$products[ $product_id ]   = $productdata;
			$data                      = $tag_config->prepare_promo_tag_products( $products );
		}
		return $data;
	}

	/**
	 * Get details of cart products.
	 *
	 * @return array
	 */
	public function retrieve_cart_product_data() {
		if ( 1 !== $this->setting_config_helper->fetch_tag_rules() ) {
			return array();
		}
		global $woocommerce;
		if ( isset( $_SESSION['synchrony_product_tag'] ) ) {
			$synchrony_prod_tag = sanitize_text_field( $_SESSION['synchrony_product_tag'] );
			$product_tag_arr    = json_decode( $synchrony_prod_tag, true );
			$items              = $woocommerce->cart->get_cart();
			if ( isset( $product_tag_arr['products'] ) && count( $product_tag_arr['products'] ) === count( $items ) ) {
				return $synchrony_prod_tag;
			}
		}
		return $this->map_products();
	}

	/**
	 * Products Mapping.
	 *
	 * @return array
	 */
	public function map_products() {
		global $woocommerce;
		$data           = array();
		$tag_config     = new Synchrony_Promotag_Config();
		$products       = array();
		$client         = new Synchrony_Client();
		$tag_attributes = $client->retrieve_product_attributes();
		$items          = $woocommerce->cart->get_cart();
		if ( ! empty( $tag_attributes ) ) {
			foreach ( $items as $values ) {
				$product               = array();
				$product_obj           = $values['data'];
				$product['item_name']  = $product_obj->get_name();
				$product['item_price'] = $this->setting_config_helper->format_amount( $product_obj->get_price() * $values['quantity'] );

				foreach ( $tag_attributes as $att_code ) {
					$att_code_value = null;
					$att_code_value = $tag_config->retrieve_product_attribute_label_by_value( $att_code, $product_obj );
					if ( ! empty( $att_code_value ) ) {
						$product[ $att_code ] = $att_code_value;
					}
				}
				$products[ $values['data']->get_id() ] = $product;
			}
		}
		$data                              = $tag_config->prepare_promo_tag_products( $products );
		$_SESSION['synchrony_product_tag'] = $data;
		return $data;
	}


	/**
	 * Get Cache
	 *
	 * @param string $cache_key this is for cache key.
	 *
	 * @return array
	 */
	public function retrieve_cache( $cache_key = '' ) {
		$formatted_cache_data = array();

		if ( '' !== $cache_key && get_transient( $cache_key ) ) {
			$get_cache_data       = get_transient( $cache_key );
			$formatted_cache_data = maybe_unserialize( $get_cache_data );
		}
		return $formatted_cache_data;
	}

	/**
	 * Get Tag Determination API response
	 *
	 * @param array  $rules_sets this is rule sets.
	 * @param string $product_attributes this is product attributes.
	 *
	 * @return float
	 */
	public function retrieve_determine_tags( $rules_sets, $product_attributes ) {
		$get_evaluated_tags      = array();
		$pro_obj                 = json_decode( $product_attributes );
		$product_attributesinner = $pro_obj->products;
		$flag                    = 0;
		$final_tag_list          = '';
		if ( ! empty( $rules_sets ) && isset( $rules_sets['rulesets'] ) ) {
			foreach ( $rules_sets['rulesets'] as $rule_inner ) {
				++$flag;
				$get_evaluated_tags[] = $this->execute_rules_set( $rule_inner, $product_attributesinner );
				if ( isset( $rule_inner['promos'] ) ) {
					$final_tag_list = array(
						'type'           => 'promos',
						'final_tag_list' => $this->rule_calculation( $get_evaluated_tags ),
					);
				} elseif ( isset( $rule_inner['tags'] ) ) {
					$final_tag_list = array(
						'type'           => 'tags',
						'final_tag_list' => $this->rule_calculation( $get_evaluated_tags ),
					);
				}
			}
		}
		return $final_tag_list;
	}

	/**
	 * Rule Calculation.
	 *
	 * @param array $get_evaluated_tags this is evaluated tags.
	 *
	 * @return mixed
	 */
	public function rule_calculation( $get_evaluated_tags ) {
		$final_tag_list = array();
		$tag_list       = array();
		if ( count( $get_evaluated_tags ) > 1 ) {
			$tag_list       = $this->retrieve_tag_lists( $get_evaluated_tags );
			$tag_list       = array_unique( array_filter( $tag_list ) );
			$final_tag_list = implode( ',', $tag_list );

		} else {
			$get_evaluated_tags = array_unique( array_filter( $get_evaluated_tags ) );
			foreach ( $get_evaluated_tags as $get_evaluated_taglist ) {
				$final_tag_list = implode( ',', $get_evaluated_taglist );
			}
		}
		return $final_tag_list;
	}

	/**
	 * Get tag list array.
	 *
	 * @param array $get_evaluated_tags This is get_evaluated_tags.
	 *
	 * @return array
	 */
	public function retrieve_tag_lists( $get_evaluated_tags ) {
		$tag_list = array();
		foreach ( $get_evaluated_tags as $get_evaluated_taglist ) {
			if ( ! empty( $get_evaluated_taglist[0] ) && ( strpos( $get_evaluated_taglist[0], ',' ) !== 0 ) ) {
				$inner_tags = explode( ',', $get_evaluated_taglist[0] );
				foreach ( $inner_tags as $innertags ) {
					$tag_list[] = trim( $innertags );
				}
			} else {
				$tag_list[] = ( ! empty( $get_evaluated_taglist[0] ) ) ? trim( $get_evaluated_taglist[0] ) : '';

			}
		}
		return $tag_list;
	}
	/**
	 * Execute Rules set and handle difference logic.
	 *
	 * @param array $rule_inner This is rules inner.
	 * @param array $product_attributes this is product attributes.
	 *
	 * @return array
	 */
	public function execute_rules_set( $rule_inner, $product_attributes ) {
		$tags               = array();
		$get_tagslist       = array();
		$s_result_cnt       = 0;
		$f_result_cnt       = 0;
		$get_rules          = $rule_inner['rules'];
		$get_rules_match    = $rule_inner['match'];
		$get_tagslist_items = $this->check_ruleset_params( $rule_inner, 'tags', 'promos' );
		if ( isset( $get_tagslist_items['final_tag_list'] ) ) {
			$get_tagslist = $get_tagslist_items['final_tag_list'];
		}

		$get_tags_status = $rule_inner['status'];
		$prd_attribute   = $product_attributes[0]->productAttributes;
		$date            = new \DateTime();
		$date->setTimezone( new \DateTimeZone( 'US/Eastern' ) );
		$current_date_time = $date->format( self::DATE_TIME_FORMAT );
		$get_start_date    = isset( $rule_inner['startDateTime'] ) ? gmdate( self::DATE_TIME_FORMAT, strtotime( $rule_inner['startDateTime'] ) ) : '';
		$get_end_date      = isset( $rule_inner['endDateTime'] ) ? gmdate( self::DATE_TIME_FORMAT, strtotime( $rule_inner['endDateTime'] ) ) : '';

		$valid_time = true;
		if ( ( $get_start_date && $current_date_time < $get_start_date ) || ( $get_end_date && $current_date_time >= $get_end_date ) ) {
			$valid_time = false;
		}
		if ( 'ACTIVE' === $get_tags_status && true === $valid_time ) {
			foreach ( $get_rules as $getrule ) {
				$get_fact = $getrule['fact'];
				$tags     = $this->execute_rule_set_tags_values( $prd_attribute, $get_fact, $get_rules, $get_rules_match, $get_tagslist, $s_result_cnt, $f_result_cnt );
			}
		}
		return $tags;
	}

	/**
	 * Execute Parameter.
	 *
	 * @param array  $rule_inner this is an array rule_inner.
	 * @param string $param this is param1.
	 * @param string $param2 This is param2.
	 *
	 * @return array
	 */
	public function check_ruleset_params( $rule_inner, $param, $param2 ) {
		$result_array = array();
		if ( isset( $rule_inner[ $param ] ) ) {
			$result_array['type']           = $param;
			$result_array['final_tag_list'] = $rule_inner[ $param ];
		} elseif ( $param2 && isset( $rule_inner[ $param2 ] ) ) {
			$result_array['type']           = $param2;
			$result_array['final_tag_list'] = $rule_inner[ $param2 ];
		}
		return $result_array;
	}

	/**
	 * Execute Rules set and returns tags array.
	 *
	 * @param array  $prd_attribute this is prd_attribute.
	 * @param string $get_fact this is get_fact.
	 * @param string $get_rules This is rules.
	 * @param string $get_rules_match this is get_rules_match.
	 * @param array  $get_tagslist this is get_tagslist.
	 * @param int    $s_result_cnt this is s_result_cnt.
	 * @param int    $f_result_cnt this is f_result_cnt.
	 *
	 * @return array
	 */
	public function execute_rule_set_tags_values( $prd_attribute, $get_fact, $get_rules, $get_rules_match, $get_tagslist, $s_result_cnt, $f_result_cnt ) {
		$tags = array();
		foreach ( $prd_attribute as $prd_attributes ) {
			if ( $prd_attributes->name === $get_fact ) {
				$get_prod_value = $prd_attributes->value;
				if ( null !== $get_prod_value && $this->execute_rule( $get_rules, $get_prod_value ) ) {
					++$s_result_cnt;
				} else {
					++$f_result_cnt;
				}
				$tags = $this->execute_rule_tags( $get_rules_match, $s_result_cnt, $get_tagslist, $f_result_cnt, $get_rules );
			}
		}
		return $tags;
	}

	/**
	 * Returns tags array.
	 *
	 * @param string $get_rules_match this is get_rules_match.
	 * @param int    $s_result_cnt this is s_result_cnt.
	 * @param array  $get_tagslist this is get_tagslist.
	 * @param int    $f_result_cnt this is f_result_cnt.
	 * @param string $get_rules This is rules.
	 *
	 * @return array
	 */
	public function execute_rule_tags( $get_rules_match, $s_result_cnt, $get_tagslist, $f_result_cnt, $get_rules ) {
		$tags = array();
		if ( ( self::RULESET_MATCH_ANY === $get_rules_match ) && 0 < $s_result_cnt ) {
			$tags[] = implode( ',', $get_tagslist );
		}
		if ( ( self::RULESET_MATCH_ALL === $get_rules_match ) && 0 === $f_result_cnt && count( $get_rules ) === $s_result_cnt ) {
			$tags[] = implode( ',', $get_tagslist );
		}
		return $tags;
	}
	/**
	 * Execute Rules set and handle difference logic.
	 *
	 * @param array $rules_set This is rules set.
	 * @param array $get_prod_value this is product value.
	 *
	 * @return float
	 */
	public function execute_rule( $rules_set, $get_prod_value ) {
		$result = false;
		foreach ( $rules_set as $rulesopt ) {
			$get_operation = $rulesopt['operator'];
			if ( ! empty( $get_prod_value ) ) {
				switch ( $get_operation ) {
					case 'EQUAL':
						$result = $this->equal_not_equal_to_comparision( $get_prod_value, $rulesopt, 'EQUAL' );
						break;
					case 'NOTEQUAL':
						$result = $this->equal_not_equal_to_comparision( $get_prod_value, $rulesopt, 'NOTEQUAL' );
						break;
					case 'GREATERTHAN':
						$result = $this->greater_less_than_comparision( $get_prod_value, $rulesopt, 'GREATERTHAN' );
						break;
					case 'LESSTHAN':
						$result = $this->greater_less_than_comparision( $get_prod_value, $rulesopt, 'LESSTHAN' );
						break;
					case 'CONTAINS':
						$result = $this->contains_not_contains_comparision( $get_prod_value, $rulesopt, 'CONTAINS' );
						break;
					case 'NOT_CONTAINS':
						$result = $this->contains_not_contains_comparision( $get_prod_value, $rulesopt, 'NOT_CONTAINS' );
						break;

					default:
						break;
				}
			}
		}
		return $result;
	}
	/**
	 * Equals to and Not equals to Comparision.
	 *
	 * @param array  $get_prod_value this is product value.
	 * @param array  $rulesopt This is rulesopt.
	 * @param string $value This is value.
	 *
	 * @return bool
	 */
	public function equal_not_equal_to_comparision( $get_prod_value, $rulesopt, $value ) {
		if ( ! empty( $get_prod_value ) && ( strcasecmp( $get_prod_value, $rulesopt['value'][0] ) === 0 ) && 'EQUAL' === $value ) {
			return true;
		}
		if ( ! empty( $get_prod_value ) && ( strcasecmp( $get_prod_value, $rulesopt['value'][0] ) !== 0 ) && 'NOTEQUAL' === $value ) {
			return true;
		}
	}
	/**
	 * Greater than and Less than comparision.
	 *
	 * @param array  $get_prod_value this is product value.
	 * @param array  $rulesopt This is rulesopt.
	 * @param string $value This is value.
	 *
	 * @return bool
	 */
	public function greater_less_than_comparision( $get_prod_value, $rulesopt, $value ) {
		if ( is_numeric( $get_prod_value ) && ( strcasecmp( $get_prod_value, $rulesopt['value'][0] ) > 0 ) && 'GREATERTHAN' === $value ) {
			return true;
		}
		if ( is_numeric( $get_prod_value ) && ( strcasecmp( $get_prod_value, $rulesopt['value'][0] ) < 0 ) && 'LESSTHAN' === $value ) {
			return true;
		}
	}
	/**
	 * Not contains comparision.
	 *
	 * @param array  $get_prod_value this is product value.
	 * @param array  $rulesopt This is rulesopt.
	 * @param string $value This is value.
	 *
	 * @return bool
	 */
	public function contains_not_contains_comparision( $get_prod_value, $rulesopt, $value ) {
		if ( 'CONTAINS' === $value ) {
			return $this->contains_match( $get_prod_value, $rulesopt );
		}
		if ( 'NOT_CONTAINS' === $value ) {
			return ! $this->contains_match( $get_prod_value, $rulesopt );
		}
	}
	/**
	 * Contains match.
	 *
	 * @param array $get_prod_value this is product value.
	 * @param array $rulesopt This is rulesopt.
	 *
	 * @return bool
	 */
	public function contains_match( $get_prod_value, $rulesopt ) {
		$get_prod_value_arr = explode( ',', $get_prod_value );
		foreach ( $get_prod_value_arr as $value ) {
			if ( $value && in_array( strtolower( trim( $value ) ), array_map( 'strtolower', $rulesopt['value'] ), true ) ) {
				return true;
			}
		}
	}
	/**
	 * Get the all attributes values.
	 *
	 * @return array
	 */
	public function retrieve_all_attributes_value() {
		$attribute_data       = array();
		$attribute_taxonomies = wc_get_attribute_taxonomy_names();
		if ( ! empty( $attribute_taxonomies ) ) {
			foreach ( $attribute_taxonomies as $taxonomy ) {
				$attribute_code        = str_replace( 'pa_', '', $taxonomy );
				$terms                 = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				);
				$attribute_option_data = array();
				if ( $terms ) {
					foreach ( $terms as $value ) {
						$attribute_option_data[] = $value->name;
					}
				}
				if ( ! empty( $attribute_option_data ) ) {
					$attribute_data[ $attribute_code ] = $attribute_option_data;
				}
			}
		}
		return $attribute_data;
	}
}
