<?php
/**
 * @package Akismet
 */
class Urednideska_Widget extends WP_Widget {

	function __construct() {
		load_plugin_textdomain( 'urednideska' );
		
		parent::__construct(
			'urednideska_widget',
			__( 'Úřední deska' , 'urednideska'),
			array( 'description' => __( 'Úřední deska' , 'urednideska') )
		);

		if ( is_active_widget( false, false, $this->id_base ) ) {
			//add_action( 'wp_head', array( $this, 'css' ) );
		}
	}

	function css() {
?>

<style type="text/css">
</style>

<?php
	}

	function form( $instance ) {
		if ( $instance && isset( $instance['title'] ) ) {
			$title = $instance['title'];
		}
		else {
			$title = __( 'Úřední deska' , 'urednideska' );
		}
		
		if ( $instance && isset( $instance['url'] ) ) {
			$url = $instance['url'];
		}
		else {
			$url = "https://edeska.olomucany.cz/rest/documents";
		}
?>

		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:' , 'urednideska'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'url' ); ?>"><?php esc_html_e( 'URL:' , 'urednideska'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" type="text" value="<?php echo esc_attr( $url ); ?>" />
		</p>

<?php
	}

	function update( $new_instance, $old_instance ) {
	
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['url'] = $new_instance['url'];
		return $instance;
	}

	function widget( $args, $instance ) {
		$count = get_option( 'akismet_spam_count' );


		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'];
			echo esc_html( $instance['title'] );
			echo $args['after_title'];
		}
		$json = file_get_contents($instance["url"]);
		$json_data = json_decode($json, true);

		echo '<ul class="menu widget_nav_menu">';
		foreach($json_data as $d) {
			$d = (object)$d;
			?>
			<li>
				<a href="<?= $d->link?>" title="<?= $d->name?>"><?= self::truncate($d->name, 25) ?></a>

				<span style="background: <?= $d->color; ?>;" class="ud-badge-block"><?= $d->category; ?>
				<div style="clear:both"></div>	
			
			</li><?php

		}

		?>

			<li><a href="//edeska.olomucany.cz">Více ...</a></li>
		<?php

		echo '</ul>';
		echo $args['after_widget'];
	}
	

	public static function length(string $s): int
	{
		return function_exists('mb_strlen')
			? mb_strlen($s, 'UTF-8')
			: strlen(utf8_decode($s));
	}

	/* copy of nette/utils/stirngs */
	

		/**
	 * Checks if given string matches a regular expression pattern and returns an array with first found match and each subpattern.
	 * Argument $flag takes same arguments as function preg_match().
	 */
	public static function match(string $subject, string $pattern, int $flags = 0, int $offset = 0): ?array
	{
		if ($offset > strlen($subject)) {
			return null;
		}
		preg_match($pattern, $subject, $m, $flags, $offset);
		return $m;
	}

		/**
	 * Returns a part of UTF-8 string specified by starting position and length. If start is negative,
	 * the returned string will start at the start'th character from the end of string.
	 */
	public static function substring(string $s, int $start, int $length = null): string
	{
		if (function_exists('mb_substr')) {
			return mb_substr($s, $start, $length, 'UTF-8'); // MB is much faster
		} elseif (!extension_loaded('iconv')) {
			throw new Nette\NotSupportedException(__METHOD__ . '() requires extension ICONV or MBSTRING, neither is loaded.');
		} elseif ($length === null) {
			$length = self::length($s);
		} elseif ($start < 0 && $length < 0) {
			$start += self::length($s); // unifies iconv_substr behavior with mb_substr
		}
		return iconv_substr($s, $start, $length, 'UTF-8');
	}

	public static function truncate(string $s, int $maxLen, string $append = "\u{2026}"): string
	{
		if (self::length($s) > $maxLen) {
			$maxLen -= self::length($append);
			if ($maxLen < 1) {
				return $append;
				
			} elseif ($matches = self::match($s, '#^.{1,' . $maxLen . '}(?=[\s\x00-/:-@\[-`{-~])#us')) {
				return $matches[0] . $append;
				
			} else {
				return self::substring($s, 0, $maxLen) . $append;
			}
		}
		return $s;
	}
}
	
function urednideska_register_widgets() {
	register_widget( 'Urednideska_Widget' );
}

add_action( 'widgets_init', 'urednideska_register_widgets' );

