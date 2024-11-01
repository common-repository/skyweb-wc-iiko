<?php

namespace SkyWeb\WC_Iiko\Admin;

defined( 'ABSPATH' ) || exit;

class Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
	}

	/**
	 * Register submenu page.
	 */
	public function register_submenu_page() {
		add_submenu_page(
			'woocommerce',
			'Iiko Cloud',
			'Iiko Cloud',
			'manage_woocommerce',
			'skyweb_wc_iiko',
			array( $this, 'submenu_page_callback' ),
			10
		);
	}

	/**
	 * Submenu page callback.
	 */
	public function submenu_page_callback() {

		ob_start();
		?>

        <div id="iikoPreloader" class="iiko_preloader hidden">
            <img src="<?php echo plugin_dir_url( SKYWEB_WC_IIKO_FILE ) . 'assets/img/preloader.svg'; ?>" alt="Preloader">
        </div>

        <h1>
			<?php
			printf( esc_html__( 'IikoCloud Control Panel %sv%s%s', 'skyweb-wc-iiko' ),
				'<small>',
				SKYWEB_WC_IIKO_VERSION,
				'</small>'
			);
			?>
        </h1>

        <div class="iiko_links">
			<?php
			printf( esc_html__( '%sSettings%s', 'skyweb-wc-iiko' ),
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=skyweb_wc_iiko_settings' ) . '" target="_blank">',
				'</a>'
			);

			printf('%sWC logs%s',
				'<a href="' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . '" target="_blank">',
				'</a>'
			);
			?>
        </div>

        <form id="iikoForm" class="iiko_form" method="POST" action="#">

            <h2><?php esc_html_e( 'General information', 'skyweb-wc-iiko' ); ?></h2>

            <p><?php esc_html_e( 'Organizations, terminals, categories and products from iiko.', 'skyweb-wc-iiko' ); ?></p>

            <fieldset>
                <p>
                    <input type="submit"
                           name="get_iiko_organizations"
                           class="button button-primary iiko_form_submit"
                           value="<?php esc_attr_e( '1. Get Organizations', 'skyweb-wc-iiko' ); ?>"
                    />
                </p>

                <div id="iikoOrganizationsWrap" class="hidden">
                    <p>
                        <label for="iikoOrganizations"><?php esc_html_e( 'Organization: ', 'skyweb-wc-iiko' ); ?></label>
                        <select name="iiko_organization" id="iikoOrganizations"></select>
                    </p>
                </div>
            </fieldset>

            <fieldset>
                <p>
                    <input type="submit"
                           name="get_iiko_terminals"
                           class="button button-primary iiko_form_submit"
                           value="<?php esc_attr_e( '2. Get Terminals', 'skyweb-wc-iiko' ); ?>"
                           disabled
                    />
                </p>

                <div id="iikoTerminalsWrap" class="hidden">
                    <p>
                        <label for="iikoTerminals"><?php esc_html_e( 'Available Terminals: ', 'skyweb-wc-iiko' ); ?></label>
                        <select name="iiko_terminals" id="iikoTerminals"></select>
                    </p>
                </div>
            </fieldset>

            <fieldset>
                <p>
                    <input type="submit"
                           name="get_iiko_nomenclature"
                           id="getIikoNomenclature"
                           class="button button-primary iiko_form_submit"
                           value="<?php esc_attr_e( '3. Get Nomenclature', 'skyweb-wc-iiko' ); ?>"
                           disabled
                    />
                    <br/>
                    <small><?php esc_html_e( 'And save chosen organization and terminal to the plugin settings.', 'skyweb-wc-iiko' ) ?></small>
                </p>

                <div id="iikoNomenclatureInfoWrap" class="iiko_nomenclature_wrap hidden">
                    <h4><?php esc_html_e( 'Nomenclature general info:', 'skyweb-wc-iiko' ); ?></h4>

                    <p>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Groups: ', 'skyweb-wc-iiko' ); ?></span>
                        <span class="iiko_nomenclature_value" id="iikoNomenclatureGroups"></span>
                    </p>

                    <p>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Product Categories: ', 'skyweb-wc-iiko' ); ?></span>
                        <span class="iiko_nomenclature_value" id="iikoNomenclatureProductCategories"></span>
                    </p>

                    <p>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Products', 'skyweb-wc-iiko' ); ?></span>
                        <br/>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Dishes: ', 'skyweb-wc-iiko' ); ?></span>
                        <span class="iiko_nomenclature_value" id="iikoNomenclatureDishes"></span>
                        <br/>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Goods: ', 'skyweb-wc-iiko' ); ?></span>
                        <span class="iiko_nomenclature_value" id="iikoNomenclatureGoods"></span>
                        <br/>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Modifiers: ', 'skyweb-wc-iiko' ); ?></span>
                        <span class="iiko_nomenclature_value" id="iikoNomenclatureModifiers"></span>
                    </p>

                    <p>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Sizes: ', 'skyweb-wc-iiko' ); ?></span>
                        <span class="iiko_nomenclature_value" id="iikoNomenclatureSizes"></span>
                    </p>

                    <p>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Revision: ', 'skyweb-wc-iiko' ); ?></span>
                        <span class="iiko_nomenclature_value" id="iikoNomenclatureRevision"></span>
                    </p>
                </div>
            </fieldset>

            <h2><?php esc_html_e( 'Import', 'skyweb-wc-iiko' ); ?></h2>

            <p><?php esc_html_e( 'Categories and products to WooCommerce.', 'skyweb-wc-iiko' ); ?></p>

            <fieldset>
                <p id="iikoTempMessage">
                    <span class="iiko_temp_message">
                        <?php esc_html_e( 'Get the information from iiko first.', 'skyweb-wc-iiko' ); ?>
                    </span>
                </p>

                <div id="iikoNomenclatureImportWrap" class="hidden">
                    <p>
                        <label for="iikoGroups"><?php esc_html_e( 'Groups: ', 'skyweb-wc-iiko' ); ?></label>
                        <select name="iiko_groups" class="iiko_groups" id="iikoGroups" multiple size="16"></select>
                        <br/>
                        <small><?php esc_html_e( 'Use CTRL + click to select several groups in the list.', 'skyweb-wc-iiko' ); ?></small>
                        <br/>
                        <small><?php esc_html_e( 'Strikethrough groups are deleted groups.', 'skyweb-wc-iiko' ); ?></small>
                        <br/>
                        <small><?php esc_html_e( 'Groups highlighted in green are modifiers.', 'skyweb-wc-iiko' ); ?></small>
                    </p>

                    <p>
                        <input type="submit"
                               name="import_iiko_groups_products"
                               class="button button-primary iiko_form_submit"
                               value="<?php esc_attr_e( '4. Import selected groups and products', 'skyweb-wc-iiko' ); ?>"/>
                    </p>
                </div>

                <div id="iikoNomenclatureImportedWrap" class="hidden">
                    <h4><?php esc_html_e( 'Imported nomenclature info:', 'skyweb-wc-iiko' ); ?></h4>

                    <p>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Groups: ', 'skyweb-wc-iiko' ); ?></span>
                        <span class="iiko_nomenclature_value" id="iikoNomenclatureImportedGroups"></span>
                    </p>

                    <p>
                        <span class="iiko_nomenclature_name"><?php esc_html_e( 'Products: ', 'skyweb-wc-iiko' ); ?></span>
                        <span class="iiko_nomenclature_value" id="iikoNomenclatureImportedProducts"></span>
                    </p>
                </div>
            </fieldset>

            <h2><?php esc_html_e( 'Additional information', 'skyweb-wc-iiko' ); ?></h2>

            <p><?php esc_html_e( 'Cities, streets and payment methods from iiko to prepare the export of orders from WooCommerce to iiko.', 'skyweb-wc-iiko' ); ?></p>

            <fieldset>
                <p>
                    <input type="submit"
                           name="get_iiko_cities"
                           class="button button-primary iiko_form_submit"
                           value="<?php esc_attr_e( 'Get Cities', 'skyweb-wc-iiko' ); ?>"
                           disabled
                    />
                </p>

                <div id="iikoCitiesWrap" class="hidden">
                    <p>
                        <label for="iikoCities"><?php esc_html_e( 'Cities: ', 'skyweb-wc-iiko' ); ?></label>
                        <select name="iiko_cities" class="iiko_cities" id="iikoCities"></select>
                    </p>

                    <p>
                        <input type="submit"
                               name="get_iiko_streets"
                               class="button button-primary iiko_form_submit"
                               value="<?php esc_attr_e( 'Get Streets', 'skyweb-wc-iiko' ); ?>"
                        />
                        <br/>
                        <small><?php esc_html_e( 'And save chosen city and streets to the plugin settings.', 'skyweb-wc-iiko' ) ?></small>
                    </p>
                </div>

                <p>
                    <input type="submit"
                           name="get_iiko_payment_types"
                           class="button button-primary iiko_form_submit"
                           value="<?php esc_attr_e( 'Get Payment Types', 'skyweb-wc-iiko' ); ?>"
                           disabled
                    />
                </p>
            </fieldset>

			<?php wp_nonce_field( 'skyweb_wc_iiko_import_action', 'skyweb_wc_iiko_import_nonce' ); ?>
        </form>

        <h2 class="iiko_terminal_title">
            <label for="iikoTerminal"><?php esc_html_e( 'Terminal:', 'skyweb-wc-iiko' ); ?></label>
        </h2>

        <p class="iiko_terminal_clear">
            <button id="iikoTerminalClear" class="button"><?php esc_html_e( 'Clear', 'skyweb-wc-iiko' ); ?></button>
        </p>

        <div id="iikoTerminal" class="iiko_terminal code"></div>

		<?php
		$html = ob_get_contents();
		ob_end_clean();

		$wrapper = '<div id="iikoPage" class="iiko_page wrap">%s</div>';
		$html    = sprintf( $wrapper, $html );

		echo $html;
	}
}