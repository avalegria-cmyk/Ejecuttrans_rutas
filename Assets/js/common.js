window.postForm = async function(url, form) {
    const res = await fetch(url,{method:'POST',body:form});
    let data; try { data=await res.json(); } catch { throw new Error('No se pudo contactar al servidor. Intenta nuevamente.'); }
    if (!res.ok || data.status!=='success') throw new Error(data.message || 'No se pudo guardar.');
    return data;
};
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(()=>{});
