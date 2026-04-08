<?php
/**
 * CSS File Writer
 *
 * Handles atomic writes of the generated CSS to uploads/fluid-scale/fluid-scale.css.
 * Uses a tmp-then-rename strategy so the live file is never partially written.
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FileWriter {

	const SUBDIR   = 'fluid-scale';
	const FILENAME = 'fluid-scale.css';

	/**
	 * Write CSS string to the generated file, atomically.
	 *
	 * @param string $css The complete CSS string to write.
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public static function write( string $css ) {
		$dir = self::get_dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return new \WP_Error(
				'fluid_scale_mkdir_failed',
				sprintf(
					/* translators: %s: directory path */
					__( 'Fluid Scale: could not create directory %s', 'fluid-scale' ),
					$dir
				)
			);
		}

		$tmp  = $dir . '/fluid-scale.tmp.css';
		$dest = $dir . '/' . self::FILENAME;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = file_put_contents( $tmp, $css );
		if ( false === $written ) {
			return new \WP_Error(
				'fluid_scale_write_failed',
				sprintf(
					/* translators: %s: file path */
					__( 'Fluid Scale: could not write to %s', 'fluid-scale' ),
					$tmp
				)
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		if ( ! rename( $tmp, $dest ) ) {
			// Clean up tmp on failure
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $tmp );
			return new \WP_Error(
				'fluid_scale_rename_failed',
				sprintf(
					/* translators: %s: file path */
					__( 'Fluid Scale: could not finalize %s', 'fluid-scale' ),
					$dest
				)
			);
		}

		return true;
	}

	/**
	 * Delete the generated CSS file and its directory.
	 * Called from uninstall.php.
	 */
	public static function delete(): void {
		$dir  = self::get_dir();
		$file = $dir . '/' . self::FILENAME;
		$tmp  = $dir . '/fluid-scale.tmp.css';

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		foreach ( [ $file, $tmp ] as $path ) {
			if ( $wp_filesystem->exists( $path ) ) {
				$wp_filesystem->delete( $path );
			}
		}

		if ( $wp_filesystem->is_dir( $dir ) ) {
			// Only remove if empty after our files are gone
			$wp_filesystem->rmdir( $dir );
		}
	}

	/**
	 * Check whether the generated CSS file exists.
	 */
	public static function exists(): bool {
		return file_exists( self::get_dir() . '/' . self::FILENAME );
	}

	/**
	 * Get the absolute directory path for the generated file.
	 */
	public static function get_dir(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/' . self::SUBDIR;
	}

	/**
	 * Get the public URL for the generated stylesheet.
	 */
	public static function get_url(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/' . self::SUBDIR . '/' . self::FILENAME;
	}
}
