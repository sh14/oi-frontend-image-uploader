<?php
/**
 * Created by PhpStorm.
 * User: sh14ru
 * Date: 04.11.17
 * Time: 15:35
 */

?>
<div class="upload-image js-upload-image" data-what="<?php echo $destination; ?>"
	<?php echo $params; ?> style="<?php echo $styles; ?>">
	<div class="upload-image__preview js-preview"
	     style="background-image: url(<?php echo $image; ?>);background-size:<?php echo $size; ?>;"></div>
	<?php
	if ( $can_edit == true && ( \oifrontend\image_uploader\is_my_page() || \oifrontend\image_uploader\is_moderator() ) ) {
		?>
		<div class="upload-image__wrapper js-fileapi-wrapper">
			<label class="upload-image__browse js-browse">
				<div class="upload-image__sign js-edit-button"></div>
				<input class="upload-image__file" name="filedata" type="file">
			</label>
			<div class="upload-image__caption js-edit-button"><?php _e( 'Choose a file', 'xxx' ); ?></div>
			<div class="upload-image__upload js-upload" style="display: none;">
				<div class="upload-image__progress progress progress-success">
					<div class="js-progress bar upload-image__progress_bar "></div>
				</div>
				<span class="upload-image__progress_caption btn-txt"><?php _e( 'Uploading...', 'xxx' ); ?></span>
			</div>
		</div>
		<?php
	}
	?>
</div>

<div id="popup" class="popup" style="display: none;">
	<div class="popup__body">
		<div class="js-img"></div>
	</div>
	<div style="margin: 0 0 5px; text-align: center;">
		<div class="js-upload btn btn_browse btn_browse_small">Upload</div>
	</div>
</div>
