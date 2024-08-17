import vm from "node:vm";

if (process.argv[2] && process.argv[3])
{
    const nsigSource = process.argv[2];
    const nsigs = process.argv[3].split(",");
    let nsigsDecoded = {};

    //console.log(`source code: ${nsigSource} - nsigs: ${nsigs}`);

    const nTransformScript = new vm.Script(nsigSource);
    nsigs.forEach((nsig, idx, arr) => {
        let nsigDecoded = nTransformScript.runInNewContext({ n: nsig });
        nsigsDecoded[nsig] = nsigDecoded;
    })

    console.log(JSON.stringify(nsigsDecoded));
}
