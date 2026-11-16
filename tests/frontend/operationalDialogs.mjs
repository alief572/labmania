import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {parse,compileScript} from '@vue/compiler-sfc';
import ts from 'typescript';
import {createRenderer} from 'vue';

const renderer=createRenderer({patchProp(){},insert(){},remove(){},createElement:()=>({}),createText:()=>({}),createComment:()=>({}),setText(){},setElementText(){},parentNode:()=>null,nextSibling:()=>null});
const originalFetch=globalThis.fetch;
const requests=[];
globalThis.fetch=(url,options)=>new Promise(resolve=>requests.push({url,options,resolve}));
const moduleUrl=source=>`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`;
async function mount(name){
    const file=new URL(`../../resources/js/pages/${name}/Index.vue`,import.meta.url);
    const {descriptor}=parse(readFileSync(file,'utf8'));
    let script=ts.transpileModule(compileScript(descriptor,{id:`dialog-${name}`}).content,{compilerOptions:{module:ts.ModuleKind.ES2022,target:ts.ScriptTarget.ES2022}}).outputText;
    script=script.replace(/import (\w+) from ['"][^'"]+\.vue['"];?/g,(_,symbol)=>`const ${symbol}={};`);
    script=script.replace(/from (['"])([^'"]+)\1/g,(_,quote,path)=>`from '${path.startsWith('.')?new URL(path+'.ts',file).href:import.meta.resolve(path)}'`);
    const component=(await import(moduleUrl(script))).default;
    component.render=()=>null;
    const app=renderer.createApp(component,{rows:{total:1},filters:{},tab:'invoiced',counts:{},programs:[],years:[],canAdd:true,canManage:true,canSalesOrder:false,pending:7});
    return {app,state:app.mount({}).$.setupState};
}
try{
    for(const name of ['Invoice','Refund']){
        const {app,state}=await mount(name);
        const start=requests.length;
        const old=state.open(1);
        const fresh=state.open(2);
        assert.equal(state.loading,true);
        assert.equal(requests[start].options.signal.aborted,true);
        assert.match(requests[start+1].url,/\/2\?modal=1$/);
        requests[start+1].resolve({ok:true,json:async()=>({id:2})});
        await fresh;
        requests[start].resolve({ok:true,json:async()=>({id:1})});
        await old;
        assert.equal(state.detail.id,2,'late response cannot overwrite active detail');
        state.busy=true;state.close();
        assert.equal(state.opened,true,'pending mutation keeps parent dialog open');
        state.busy=false;state.close();
        assert.equal(state.opened,false);
        const failed=state.open(3);
        requests.at(-1).resolve({ok:false,status:403});
        await failed;
        assert.match(state.error,/tidak dapat dimuat/);
        assert.equal(state.loading,false);
        const leaving=state.open(4);
        app.unmount();
        assert.equal(requests.at(-1).options.signal.aborted,true);
        requests.at(-1).resolve({ok:true,json:async()=>({id:4})});
        await leaving;
        assert.equal(state.detail,null,'unmounted detail cannot receive response');
    }
    console.log('operationalDialogs: abort/race, error, pending guard dan unmount lulus.');
}finally{globalThis.fetch=originalFetch;}
