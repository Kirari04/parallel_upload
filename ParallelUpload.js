class ParallelUpload {
  file = null;
  origin = null;
  fs = null;
  slicesize = 100000 * 10; // 10 mb
  constructor(file) {
    this.file = file;
    console.ParallelUpload = e => console.log(e);
  }
  api(origin){
    this.origin = origin;
    return this;
  }
  upload(){
    return new Promise(async (resolve, reject) => {
        console.ParallelUpload(this.file.size);
        let slices = Math.ceil(this.file.size / this.slicesize);
        console.ParallelUpload(`slices: ${slices}`);
        for (let i = 0; i < slices; i++) {
            let floor = this.slicesize * i;
            let ceil = this.slicesize * (i + 1);
            let part = this.file.slice(floor, ceil);;
            console.ParallelUpload(`part: ${part}`);
            
            const formData = new FormData();
            formData.append('file', this.file, this.file.name);
            formData.append("parts", slices);
            formData.append("part", i + 1);
            await fetch(this.origin, {
                method: 'POST',
                body: formData
            })
            .then(response => response.body())
            .then(data => console.ParallelUpload(data))
        }
        resolve('done');
    });
  }
}