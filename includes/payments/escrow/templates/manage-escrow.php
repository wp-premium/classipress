<?php foreach( $fields as $key => $section ): ?>

	<div class="large-12 columns">
		<div class="escrow-fields">
			<?php if ( ! empty( $section['title'] ) ): ?>
				<h3><?php echo $section['title']; ?></h3>
			<?php endif; ?>

			<?php
				$output = '';

				foreach ( $section['fields'] as $field ) {
					$output .= scbForms::table_row( $field, $formdata );
				}

				echo html( "table class='form-table escrow-gateway-fields' width='100%'", $output );
			?>
		</div>
	</div>

<?php endforeach; ?>
