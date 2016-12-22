<form action="" method="POST" class="gateway">
	<fieldset>

		<div class="form-field"><label>
			Text Field
			<input type="text" />
		</label></div>

		<div class="form-field"><label>
			Short Field (CVV)
			<input type="text" size="4" maxlength="4" />
		</label></div>

		<div class="form-field"><label>
			Two-Box Field
			<input type="text" size="2" maxlength="2" />
			<span> / </span>
			<input type="text" size="4" maxlength="4" />
		</label></div>

		<div class="form-field"><label>
			Textarea Field
			<textarea name="textarea"></textarea>
		</label></div>

		<div class="form-field"><label>
			Select Field
			<select name="select">
				<option value="Option 1">Option 1</option>
				<option value="Option 2">Option 2</option>
			</select>
		</label></div>

		<div class="form-field">
			<label><input type="checkbox" name="checkbox[]" />Checkbox Option 1</label>
			<label><input type="checkbox" name="checkbox[]" />Checkbox Option 2</label>
		</div>

		<div class="form-field">
			<label><input type="radio" name="radio" />Radio Option 1</label>
			<label><input type="radio" name="radio" />Radio Option 2</label>
		</div>

		<input type="submit" class="button" value="Submit Button" />

	</fieldset>
</form>