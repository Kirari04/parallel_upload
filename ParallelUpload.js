class ParallelUpload {
    file = null;
    origin = null;
    fs = null;
    progress = null;
    slicesize = 1000000 * 40; // 10 mb
    parallel = 5;
    constructor(file) {
        this.file = file;
        console.ParallelUpload = (e) => null;
    }
    api(origin) {
        this.origin = origin;
        return this;
    }
    progressbar(el) {
        this.progress = el;
        return this;
    }
    upload() {
        return new Promise(async (resolve, reject) => {
            console.ParallelUpload(this.file.size);
            let slices = Math.ceil(this.file.size / this.slicesize);
            console.ParallelUpload(`slices: ${slices}`);
            console.ParallelUpload(
                `partsizes: ${this.humanFileSize(this.slicesize)}`
            );
			let todo = [...Array(slices).keys()].map(e => false);
			let running = 0;
            let intv = setInterval(() => {
				while(running < this.parallel && todo.findIndex(e => e === false) > -1){
					console.info('add new upload')
					let index = todo.findIndex(e => e === false);
					if(index > -1){
						running++;
						todo[index] = true;
						this.runUpload(index, slices)
						.then(() => {
							running--;
						});
					}
				}
				if(todo.findIndex(e => e === false) === -1 && running === 0){
					clearInterval(intv);
					this.progress.value = 100;
					resolve("done");
				}
            }, 100);
        });
    }
    async runUpload(i, slices) {
        return new Promise(async (resolve, reject) => {
            let floor = this.slicesize * i;
            let ceil = this.slicesize * (i + 1);

            let floor_percent = Math.round((100 / slices) * i);
            let ceil_percent = Math.round((100 / slices) * (i + 1));

            let part = this.file.slice(floor, ceil);
            console.ParallelUpload(`part: ${part}`);
            console.ParallelUpload(
                `partsize: ${this.humanFileSize(part.size)}`
            );

            const formData = new FormData();
            formData.append("file", part, this.file.name);
            formData.append("parts", slices);
            formData.append("part", i + 1);
            await fetch(this.origin, {
                method: "POST",
                body: formData,
            })
                .then((response) => response.text())
                .then((data) => console.ParallelUpload(data));
            if (this.progress) {
                console.info(`Uploaded: ${ceil_percent}%`);
                this.progress.value = ceil_percent;
            }
            resolve(true);
        });
    }
    humanFileSize(bytes, si = false, dp = 1) {
        const thresh = si ? 1000 : 1024;

        if (Math.abs(bytes) < thresh) {
            return bytes + " B";
        }

        const units = si
            ? ["kB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"]
            : ["KiB", "MiB", "GiB", "TiB", "PiB", "EiB", "ZiB", "YiB"];
        let u = -1;
        const r = 10 ** dp;

        do {
            bytes /= thresh;
            ++u;
        } while (
            Math.round(Math.abs(bytes) * r) / r >= thresh &&
            u < units.length - 1
        );

        return bytes.toFixed(dp) + " " + units[u];
    }
}
