$(() => {
	$('input[name="item06"]').on('change', (ev) => {
		const val = $(ev.currentTarget).val();
		if (val === '学生・大学院生') {
			$('input[name="item07"]').prop('required', true);
			$('.school-year').show();
		} else {
			$('input[name="item07"]').prop('required', false).prop('checked', false).val('');
			$('.school-year').hide();
		}
	});
});