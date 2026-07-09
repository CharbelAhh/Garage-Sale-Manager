document.addEventListener('DOMContentLoaded', function () {
	const prevBtn = document.getElementById('prev-month-btn');
	const nextBtn = document.getElementById('next-month-btn');
	const monthHeading = document.getElementById('report-month');

	if (!monthHeading) return;

	let year = parseInt(monthHeading.getAttribute('data-year'), 10) || new Date().getFullYear();
	let month = parseInt(monthHeading.getAttribute('data-month'), 10) || (new Date().getMonth() + 1);

	function updateHeading() {
		const dt = new Date(year, month - 1, 1);
		const opts = { year: 'numeric', month: 'long' };
		monthHeading.textContent = dt.toLocaleDateString(undefined, opts);
		monthHeading.setAttribute('data-year', year);
		monthHeading.setAttribute('data-month', month);
	}

	function fetchReports(y, m) {
		const xhr = new XMLHttpRequest();
		xhr.open('POST', 'components/get_reports_by_month.php', true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4) {
				if (xhr.status === 200) {
					// Replace the data-count and most-popular sections with returned HTML
					const containerEarnings = document.getElementById('earning-data');
					const containerData = document.getElementById('data-count');
					const containerPopular = document.getElementById('most-popular');
					const temp = document.createElement('div');
					temp.innerHTML = xhr.responseText;
					const newEarnings = temp.querySelector('#earning-data');
					const newData = temp.querySelector('#data-count');
					const newPopular = temp.querySelector('#most-popular');
					if (containerEarnings && newEarnings) {
						containerEarnings.parentNode.replaceChild(newEarnings, containerEarnings);
					}
					if (containerData && newData) {
						containerData.parentNode.replaceChild(newData, containerData);
					}
					if (containerPopular && newPopular) {
						containerPopular.parentNode.replaceChild(newPopular, containerPopular);
					}
					// If one of the sections wasn't found, as a fallback replace the whole report area
					if ((!containerData || !newData) && (!containerPopular || !newPopular)) {
						const main = document.getElementById('reports-main');
						if (main) main.innerHTML = xhr.responseText;
					}
				} else {
					console.error('Failed to load reports:', xhr.statusText);
				}
			}
		};
		xhr.send('year=' + encodeURIComponent(y) + '&month=' + encodeURIComponent(m));
	}

	function goToPreviousMonth() {
		month -= 1;
		if (month < 1) { month = 12; year -= 1; }
		updateHeading();
		fetchReports(year, month);
	}

	function goToNextMonth() {
		month += 1;
		if (month > 12) { month = 1; year += 1; }
		updateHeading();
		fetchReports(year, month);
	}

	prevBtn && prevBtn.addEventListener('click', function (e) { e.preventDefault(); goToPreviousMonth(); });
	nextBtn && nextBtn.addEventListener('click', function (e) { e.preventDefault(); goToNextMonth(); });
});
