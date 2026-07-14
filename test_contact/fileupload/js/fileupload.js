const inputFile = document.getElementById('inputFile');
const submit = document.getElementById('submit');

const app = Vue.createApp({
	data() {
		return {
			isEnter: false,
			isFiles: false,
			isOversize: false,
			files: [],
			filesize: 0
		};
	},
	methods: {
		dragEnter() {
			this.isEnter = true;
		},
		dragLeave() {
			this.isEnter = false;
		},
		dropFile(ev) {
			Array.from(ev.dataTransfer.files).forEach(item => {
				this.filedata(item);
			});
			this.isEnter = false;
		},
		changeFile(ev) {
			Array.from(ev.target.files).forEach(item => {
				this.filedata(item);
			});
		},
		deleteFile(index) {
			this.files.splice(index, 1);
			this.calcFilesize();
			if (this.files.length === 0) {
				this.isFiles = false;
			}
		},
		deleteAll() {
			this.files = [];
			this.calcFilesize();
			this.isFiles = false;
		},
		filedata: function (item) {
			const fileData = item;
			this.files.push(fileData);
			this.calcFilesize();
			this.isFiles = true;
		},
		calcFilesize: function () {
			let n = 0;
			this.files.forEach(item => {
				n += item.size;
			});
			//this.filesize = n;
			this.filesize = size_convert(n, 2);
			if (n > allowedSize) { // 2023/06/16 ">=" -> ">"
				this.isOversize = true;
				submit.classList.add('disabled');
			} else {
				this.isOversize = false;
				submit.classList.remove('disabled');
			}
		},
		sendFileData() {
			return this.files;
		}
	}
});

const fileUpload = app.mount("#fileUpload");

function submitForm() {
	const $form = $('#form01');
	const form = $form.get(0);
	const key = $('#inputFile').attr('name');
	const url = $form.attr('action');
	const files = fileUpload.sendFileData();
	const fd = new FormData(form);

	fd.delete(key);
	for (let i = 0; i < files.length; ++i) {
		fd.append(key, files[i]);
	}

	const newForm = document.createElement("form");
	newForm.action = url;
	newForm.method = "post";
	newForm.enctype = "multipart/form-data";
	newForm.addEventListener("formdata", ev => {
		for (const [name, value] of fd.entries()) {
			ev.formData.append(name, value);
		}
	});
	document.body.append(newForm);
	newForm.submit();
}

// ファイルサイズ計算 2023/06/16
// bite    @ ファイルサイズ
// decimal @ 小数点桁数(デフォルトは1) [1:0.1 , 2:0.01 , 3:0.001...]
function size_convert(bite, decimal) {
	decimal = (decimal) ? Math.pow(10, decimal) : 10;
	var kiro = 1024;
	var size = bite;
	var unit = "B";
	var units = ["B", "KB", "MB", "GB", "TB"];
	for (var i = (units.length - 1); i > 0; i--) {
		if (bite / Math.pow(kiro, i) > 1) {
			size = Math.round(bite / Math.pow(kiro, i) * decimal) / decimal;
			unit = units[i];
			break;
		}
	}
	return String(size) + " " + unit;
}