<?php

// +----------------------------------------------------------------------+
// | Copyright 2013  Madpixels  (email : visualizer@madpixels.net)        |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+
// | Author: Eugene Manuilov <eugene@manuilov.org>                        |
// +----------------------------------------------------------------------+

/**
 * The module for all admin stuff.
 *
 * @category Visualizer
 * @package Module
 *
 * @since 1.0.0
 */
class Visualizer_Module_Admin extends Visualizer_Module {

	const NAME = __CLASS__;

	/**
	 * Library page suffix.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @var string
	 */
	private $_libraryPage;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param Visualizer_Plugin $plugin The instance of the plugin.
	 */
	public function __construct( Visualizer_Plugin $plugin ) {
		parent::__construct( $plugin );

		$this->_addAction( 'load-post.php', 'enqueueMediaScripts' );
		$this->_addAction( 'load-post-new.php', 'enqueueMediaScripts' );
		$this->_addAction( 'admin_footer', 'renderTempaltes' );
		$this->_addAction( 'admin_enqueue_scripts', 'enqueueLibraryScripts' );
		$this->_addAction( 'admin_menu', 'registerAdminMenu' );

		$this->_addFilter( 'media_view_strings', 'setupMediaViewStrings' );
		$this->_addFilter( 'plugin_action_links', 'getPluginActionLinks', 10, 2 );
		$this->_addFilter( 'plugin_row_meta', 'getPluginMetaLinks', 10, 2 );
	}

	/**
	 * Returns associated array of chart types and localized names.
	 *
	 * @since 1.0.0
	 *
	 * @static
	 * @access private
	 * @return array The associated array of chart types with localized names.
	 */
	private static function _getChartTypesLocalized() {
		return array(
			'all'         => esc_html__( 'All', Visualizer_Plugin::NAME ),
			'pie'         => esc_html__( 'Pie', Visualizer_Plugin::NAME ),
			'line'        => esc_html__( 'Line', Visualizer_Plugin::NAME ),
			'area'        => esc_html__( 'Area', Visualizer_Plugin::NAME ),
			'geo'         => esc_html__( 'Geo', Visualizer_Plugin::NAME ),
			'bar'         => esc_html__( 'Bar', Visualizer_Plugin::NAME ),
			'column'      => esc_html__( 'Column', Visualizer_Plugin::NAME ),
			'gauge'       => esc_html__( 'Gauge', Visualizer_Plugin::NAME ),
			'scatter'     => esc_html__( 'Scatter', Visualizer_Plugin::NAME ),
			'candlestick' => esc_html__( 'Candelstick', Visualizer_Plugin::NAME ),
		);
	}

	/**
	 * Enqueues media scripts and styles.
	 *
	 * @since 1.0.0
	 * @uses wp_enqueue_style To enqueue style file.
	 * @uses wp_enqueue_script To enqueue script file.
	 *
	 * @access public
	 */
	public function enqueueMediaScripts() {
		wp_enqueue_style( 'visualizer-media', VISUALIZER_ABSURL . 'css/media.css', array( 'media-views' ), Visualizer_Plugin::VERSION );

		wp_enqueue_script( 'google-jsapi',               '//www.google.com/jsapi',                      array( 'media-editor' ),                null );
		wp_enqueue_script( 'visualizer-media-model',      VISUALIZER_ABSURL . 'js/media/model.js',      array( 'google-jsapi' ),                Visualizer_Plugin::VERSION );
		wp_enqueue_script( 'visualizer-media-collection', VISUALIZER_ABSURL . 'js/media/collection.js', array( 'visualizer-media-model' ),      Visualizer_Plugin::VERSION );
		wp_enqueue_script( 'visualizer-media-controller', VISUALIZER_ABSURL . 'js/media/controller.js', array( 'visualizer-media-collection' ), Visualizer_Plugin::VERSION );
		wp_enqueue_script( 'visualizer-media-view',       VISUALIZER_ABSURL . 'js/media/view.js',       array( 'visualizer-media-controller' ), Visualizer_Plugin::VERSION );
		wp_enqueue_script( 'visualizer-media-toolbar',    VISUALIZER_ABSURL . 'js/media/toolbar.js',    array( 'visualizer-media-view' ),       Visualizer_Plugin::VERSION );
		wp_enqueue_script( 'visualizer-media',            VISUALIZER_ABSURL . 'js/media.js',            array( 'visualizer-media-toolbar' ),    Visualizer_Plugin::VERSION );
	}

	/**
	 * Extends media view strings with visualizer strings.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array $strings The array of media view strings.
	 * @return array The extended array of media view strings.
	 */
	public function setupMediaViewStrings( $strings ) {
		$strings['visualizer'] = array(
			'actions' => array(
				'get_charts'   => Visualizer_Plugin::ACTION_GET_CHARTS,
				'delete_chart' => Visualizer_Plugin::ACTION_DELETE_CHART,
			),
			'controller' => array(
				'title' => esc_html__( 'Visualizations', Visualizer_Plugin::NAME ),
			),
			'routers' => array(
				'library' => esc_html__( 'From Library', Visualizer_Plugin::NAME ),
				'create'  => esc_html__( 'Create New', Visualizer_Plugin::NAME ),
			),
			'library' => array(
				'filters' => self::_getChartTypesLocalized(),
			),
			'nonce'    => Visualizer_Security::createNonce(),
			'buildurl' => add_query_arg( 'action', Visualizer_Plugin::ACTION_CREATE_CHART, admin_url( 'admin-ajax.php' ) ),
		);

		return $strings;
	}

	/**
	 * Renders templates to use in media popup.
	 *
	 * @since 1.0.0
	 * @global string $pagenow The name of the current page.
	 *
	 * @access public
	 */
	public function renderTempaltes() {
		global $pagenow;

		if ( 'post.php' != $pagenow && 'post-new.php' != $pagenow ) {
			return;
		}

		$render = new Visualizer_Render_Templates();
		$render->render();
	}

	/**
	 * Enqueues library scripts and styles.
	 *
	 * @since 1.0.0
	 * @uses wp_enqueue_style() To enqueue library stylesheet.
	 * @uses wp_enqueue_script() To enqueue javascript file.
	 * @uses wp_enqueue_media() To enqueue media stuff.
	 *
	 * @access public
	 * @param string $suffix The current page suffix.
	 */
	public function enqueueLibraryScripts( $suffix ) {
		if ( $suffix == $this->_libraryPage ) {
			wp_enqueue_style( 'visualizer-library', VISUALIZER_ABSURL . 'css/library.css', array(), Visualizer_Plugin::VERSION );

			$this->_addFilter( 'media_upload_tabs', 'setupVisualizerTab' );

			wp_enqueue_media();
			wp_enqueue_script( 'visualizer-library', VISUALIZER_ABSURL . 'js/library.js', array( 'jquery', 'media-views' ), Visualizer_Plugin::VERSION, true );
			wp_enqueue_script( 'google-jsapi', '//www.google.com/jsapi', array(), null, true );
			wp_enqueue_script( 'visualizer-render', VISUALIZER_ABSURL . 'js/render.js', array( 'google-jsapi', 'visualizer-library' ), Visualizer_Plugin::VERSION, true );
		}
	}

	/**
	 * Adds visualizer tab for media upload tabs array.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array $tabs The array of media upload tabs.
	 * @return array Extended array of media upload tabs.
	 */
	public function setupVisualizerTab( $tabs ) {
		$tabs['visualizer'] = 'Visualizer';
		return $tabs;
	}

	/**
	 * Registers admin menu for visualizer library.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function registerAdminMenu() {
		$title = esc_html__( 'Visualizer Library', Visualizer_Plugin::NAME );
		$callback = array( $this, 'renderLibraryPage' );
		$this->_libraryPage = add_submenu_page( 'upload.php', $title, $title, 'edit_posts', Visualizer_Plugin::NAME, $callback );
	}

	/**
	 * Renders visualizer library page.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function renderLibraryPage() {
		// get current page
		$page = filter_input( INPUT_GET, 'vpage', FILTER_VALIDATE_INT, array(
			'options' => array(
				'min_range' => 1,
				'default'   => 1,
			)
		) );

		// the initial query arguments to fetch charts
		$query_args = array(
			'post_type'      => Visualizer_Plugin::CPT_VISUALIZER,
			'posts_per_page' => 6,
			'paged'          => $page,
		);

		// add chart type filter to the query arguments
		$type = filter_input( INPUT_GET, 'type' );
		if ( $type && in_array( $type, Visualizer_Plugin::getChartTypes() ) ) {
			$query_args['meta_query'] = array(
				array(
					'key'     => Visualizer_Plugin::CF_CHART_TYPE,
					'value'   => $type,
					'compare' => '=',
				),
			);
		} else {
			$type = 'all';
		}

		// fetch charts
		$charts = array();
		$query = new WP_Query( $query_args );
		while( $query->have_posts() ) {
			$chart = $query->next_post();

			// faetch and update settings
			$settings = get_post_meta( $chart->ID, Visualizer_Plugin::CF_SETTINGS, true );
			unset( $settings['height'], $settings['width'] );

			// add chart to the array
			$charts['visualizer-' . $chart->ID] = array(
				'id'       => $chart->ID,
				'type'     => get_post_meta( $chart->ID, Visualizer_Plugin::CF_CHART_TYPE, true ),
				'series'   => get_post_meta( $chart->ID, Visualizer_Plugin::CF_SERIES, true ),
				'settings' => $settings,
				'data'     => unserialize( $chart->post_content ),
			);
		}

		// enqueue charts array
		$ajaxurl = admin_url( 'admin-ajax.php' );
		wp_localize_script( 'visualizer-library', 'visualizer', array(
			'charts' => $charts,
			'urls'   => array(
				'base'   => add_query_arg( 'vpage', false ),
				'create' => add_query_arg( array( 'action' => Visualizer_Plugin::ACTION_CREATE_CHART, 'library' => 'yes' ), $ajaxurl ),
				'edit'   => add_query_arg( array( 'action' => Visualizer_Plugin::ACTION_EDIT_CHART,   'library' => 'yes' ), $ajaxurl ),
			),
		) );

		// render library page
		$render = new Visualizer_Render_Library();

		$render->charts = $charts;
		$render->type = $type;
		$render->types = self::_getChartTypesLocalized();
		$render->pagination = paginate_links( array(
			'base'    => add_query_arg( 'vpage', '%#%' ),
			'format'  => '',
			'current' => $page,
			'total'   => $query->max_num_pages,
			'type'    => 'array',
		) );

		$render->render();
	}

	/**
	 * Updates the plugin's action links, which will be rendered at the plugins table.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array $links The array of original action links.
	 * @param string $file The plugin basename.
	 * @return array Updated array of action links.
	 */
	public function getPluginActionLinks( $links, $file ) {
		if ( $file == plugin_basename( VISUALIZER_BASEFILE ) ) {
			array_unshift(
				$links,
				sprintf(
					'<a href="%s">%s</a>',
					admin_url( 'upload.php?page=' . Visualizer_Plugin::NAME ),
					esc_html__( 'Library', Visualizer_Plugin::NAME )
				)
			);
		}

		return $links;
	}

	/**
	 * Updates the plugin's meta links, which will be rendered at the plugins table.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array $plugin_meta The array of a plugin meta links.
	 * @param string $plugin_file The plugin's basename.
	 * @return array Updated array of plugin meta links.
	 */
	public function getPluginMetaLinks( $plugin_meta, $plugin_file ) {
		if ( $plugin_file == plugin_basename( VISUALIZER_BASEFILE ) ) {
			// knowledge base link
			$plugin_meta[] = sprintf(
				'<a href="http://visualizer.madpixels.net/knowledgebase/">%s</a>',
				esc_html__( 'Knowledge Base' )
			);

			// community link
			$plugin_meta[] = sprintf(
				'<a href="http://visualizer.madpixels.net/forums/">%s</a>',
				esc_html__( 'Community', Visualizer_Plugin::NAME )
			);

			// github link
			$plugin_meta[] = '<a href="https://github.com/madpixelslabs/visualizer">GitHub Repository</a>';
		}

		return $plugin_meta;
	}

}