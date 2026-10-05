(function () {
  'use strict';

  const config = window.PLAN_IMPORT_PREVIEW_CONFIG || {};
  const root = document.getElementById('plan-import-preview-root');
  if (!root) return;
  let preview = null;
  let dirty = false;
  let confirming = false;

  function element(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function apiMessage(data, fallback) {
    return data && (data.message || data.error) || fallback;
  }

  async function request(url, options) {
    const response = await fetch(url, Object.assign({ credentials: 'same-origin' }, options || {}));
    const body = await response.json().catch(function () { return null; });
    if (!response.ok || !body || body.success !== true) throw new Error(apiMessage(body, 'No se pudo procesar el preview.'));
    return body.data;
  }

  function setValue(target, key, raw, type) {
    if (type === 'number') target[key] = raw === '' ? null : Number(raw);
    else target[key] = raw.trim() === '' ? null : raw;
    dirty = true;
    updateStateLabel();
  }

  function field(label, target, key, options) {
    options = options || {};
    const wrapper = element('label', options.wide ? 'font-semibold sm:col-span-2' : 'font-semibold');
    wrapper.appendChild(document.createTextNode(label));
    const control = document.createElement(options.multiline ? 'textarea' : 'input');
    control.className = 'app-input mt-2 w-full';
    if (options.multiline) control.rows = options.rows || 2;
    else control.type = options.type || 'text';
    if (options.type === 'number') { control.min = options.min || '0'; control.step = options.step || '1'; }
    control.value = target[key] === null || target[key] === undefined ? '' : String(target[key]);
    control.addEventListener('input', function () { setValue(target, key, control.value, options.type); });
    wrapper.appendChild(control);
    return wrapper;
  }

  function titleSummary(prefix, code, text) {
    return [prefix, code, text].filter(function (value) { return value !== null && value !== undefined && String(value).trim() !== ''; }).join(' · ');
  }

  function mutateList(list, action) {
    action();
    list.forEach(function (item, index) { if (item && Object.prototype.hasOwnProperty.call(item, 'orden')) item.orden = index + 1; });
    dirty = true; render();
  }

  function smallButton(text, action, danger) {
    const button = element('button', 'btn ' + (danger ? 'btn-danger' : 'btn-secondary'), text);
    button.type = 'button'; button.addEventListener('click', action); return button;
  }

  function renderStringList(title, values) {
    const section = element('div', 'plan-import-list');
    section.appendChild(element('h6', 'font-bold mb-2', title));
    values.forEach(function (value, index) {
      const label = element('label', 'plan-import-inline-field');
      label.appendChild(element('span', '', String(index + 1)));
      const input = document.createElement('input');
      input.className = 'app-input w-full'; input.value = value;
      input.addEventListener('input', function () { values[index] = input.value; dirty = true; updateStateLabel(); });
      label.appendChild(input);
      label.appendChild(smallButton('Quitar', function(){ mutateList(values,function(){values.splice(index,1);}); }, true));
      section.appendChild(label);
    });
    if (!values.length) section.appendChild(element('p', 'text-sm', 'Sin datos extraídos.'));
    section.appendChild(smallButton('Agregar', function(){mutateList(values,function(){values.push('Nuevo valor');});}, false));
    return section;
  }

  function renderProgramming(topic, unitOrder, capacityOrder) {
    const section=element('div','plan-import-list');
    section.appendChild(element('h6','font-bold mb-2','Programación exacta confirmada'));
    section.appendChild(element('p','text-sm mb-2','El texto de fecha se conserva. No se convierte automáticamente en fechas exactas.'));
    const rows=preview.programming.filter(function(row){return row.unidad_orden===unitOrder&&row.capacidad_orden===capacityOrder&&row.tema_orden===topic.orden;});
    rows.forEach(function(row){
      const grid=element('div','app-form-grid plan-import-programming-row');
      [['Inicio','fecha_inicio','date'],['Fin','fecha_fin','date'],['Horas','horas_catedra_planificadas','number'],['Observaciones','observaciones','text']].forEach(function(def){grid.appendChild(field(def[0],row,def[1],{type:def[2],step:'0.01'}));});
      grid.appendChild(smallButton('Quitar programación',function(){mutateList(preview.programming,function(){preview.programming.splice(preview.programming.indexOf(row),1);});},true));
      section.appendChild(grid);
    });
    section.appendChild(smallButton('Agregar fechas confirmadas',function(){
      preview.programming.push({unidad_orden:unitOrder,capacidad_orden:capacityOrder,tema_orden:topic.orden,fecha_inicio:'',fecha_fin:'',horas_catedra_planificadas:null,observaciones:null});dirty=true;render();
    },false));
    return section;
  }

  function renderIndicator(indicator, index, indicators) {
    const card = element('div', 'plan-import-indicator');
    card.appendChild(element('div', 'font-bold mb-2', 'Indicador ' + (index + 1)));
    const grid = element('div', 'app-form-grid');
    grid.appendChild(field('Código', indicator, 'codigo'));
    grid.appendChild(field('Descripción', indicator, 'descripcion', { multiline:true, wide:true }));
    card.appendChild(grid);
    card.appendChild(smallButton('Quitar indicador',function(){mutateList(indicators,function(){indicators.splice(index,1);});},true));
    return card;
  }

  function renderTopic(topic, index, topics, unitOrder, capacityOrder) {
    const details = element('details', 'plan-import-node plan-import-topic'); details.open = index === 0;
    details.appendChild(element('summary', 'plan-import-summary', titleSummary('TEMA ' + (index + 1), topic.codigo, topic.titulo)));
    const body = element('div', 'plan-import-node-body');
    const grid = element('div', 'app-form-grid');
    grid.appendChild(field('Código', topic, 'codigo'));
    grid.appendChild(field('Título', topic, 'titulo', { wide:true }));
    grid.appendChild(field('Contenido', topic, 'contenido', { multiline:true, wide:true }));
    grid.appendChild(field('Horas cátedra', topic, 'horas_catedra', { type:'number', step:'0.01' }));
    grid.appendChild(field('Tiempo (texto fuente)', topic, 'tiempo_texto'));
    grid.appendChild(field('Fecha (texto fuente)', topic, 'fecha_texto', { wide:true }));
    body.appendChild(grid);
    const indicators = element('div', 'plan-import-children');
    indicators.appendChild(element('h5', 'font-bold mb-2', 'Indicadores'));
    topic.indicadores.forEach(function (indicator, indicatorIndex) { indicators.appendChild(renderIndicator(indicator, indicatorIndex, topic.indicadores)); });
    indicators.appendChild(smallButton('Agregar indicador',function(){mutateList(topic.indicadores,function(){topic.indicadores.push({orden:topic.indicadores.length+1,codigo:null,descripcion:'Nuevo indicador',check:null});});},false));
    body.appendChild(indicators);
    const evaluation = element('div', 'plan-import-evaluation-grid');
    evaluation.appendChild(renderStringList('Procedimientos evaluativos', topic.procedimientos_evaluativos));
    evaluation.appendChild(renderStringList('Instrumentos evaluativos', topic.instrumentos_evaluativos));
    body.appendChild(evaluation);
    body.appendChild(renderProgramming(topic,unitOrder,capacityOrder));
    body.appendChild(smallButton('Quitar tema',function(){mutateList(topics,function(){topics.splice(index,1);});},true));
    details.appendChild(body); return details;
  }

  function renderCapacity(capacity, index, capacities, unitOrder) {
    const details = element('details', 'plan-import-node plan-import-capacity'); details.open = true;
    details.appendChild(element('summary', 'plan-import-summary', titleSummary('CAPACIDAD ' + (index + 1), null, capacity.descripcion)));
    const body = element('div', 'plan-import-node-body');
    const grid = element('div', 'app-form-grid');
    grid.appendChild(field('Capacidad', capacity, 'descripcion', { multiline:true, wide:true }));
    grid.appendChild(field('Proceso de desarrollo', capacity, 'proceso_desarrollo', { multiline:true, wide:true }));
    body.appendChild(grid);
    const topics = element('div', 'plan-import-children');
    capacity.temas.forEach(function (topic, topicIndex) { topics.appendChild(renderTopic(topic, topicIndex, capacity.temas, unitOrder, capacity.orden)); });
    topics.appendChild(smallButton('Agregar tema',function(){mutateList(capacity.temas,function(){capacity.temas.push({orden:capacity.temas.length+1,codigo:null,titulo:'Nuevo tema',contenido:null,horas_catedra:null,tiempo_texto:null,fecha_texto:null,indicadores:[{orden:1,codigo:null,descripcion:'Nuevo indicador',check:null}],procedimientos_evaluativos:[],instrumentos_evaluativos:[]});});},false));
    body.appendChild(topics);
    body.appendChild(smallButton('Quitar capacidad',function(){mutateList(capacities,function(){capacities.splice(index,1);});},true));
    details.appendChild(body); return details;
  }

  function renderUnit(unit, index, units) {
    const details = element('details', 'plan-import-node plan-import-unit'); details.open = true;
    details.appendChild(element('summary', 'plan-import-summary', titleSummary('UNIDAD ' + (index + 1), unit.codigo, unit.nombre)));
    const body = element('div', 'plan-import-node-body');
    const grid = element('div', 'app-form-grid');
    grid.appendChild(field('Código', unit, 'codigo'));
    grid.appendChild(field('Nombre', unit, 'nombre', { wide:true }));
    grid.appendChild(field('Descripción', unit, 'descripcion', { multiline:true, wide:true }));
    grid.appendChild(field('Horas cátedra', unit, 'horas_catedra', { type:'number', step:'0.01' }));
    grid.appendChild(field('Tiempo (texto fuente)', unit, 'tiempo_texto'));
    grid.appendChild(field('Proceso (texto fuente)', unit, 'proceso_texto', { multiline:true, wide:true }));
    grid.appendChild(field('Área transversal', unit, 'area_transversal', { multiline:true, wide:true }));
    grid.appendChild(field('Metodología', unit, 'metodologia', { multiline:true, wide:true }));
    grid.appendChild(field('Medios de verificación', unit, 'medios_verificacion', { multiline:true, wide:true }));
    body.appendChild(grid);
    const capacities = element('div', 'plan-import-children');
    unit.capacidades.forEach(function (capacity, capacityIndex) { capacities.appendChild(renderCapacity(capacity, capacityIndex, unit.capacidades, unit.orden)); });
    capacities.appendChild(smallButton('Agregar capacidad',function(){mutateList(unit.capacidades,function(){unit.capacidades.push({orden:unit.capacidades.length+1,descripcion:'Nueva capacidad',proceso_desarrollo:null,temas:[{orden:1,codigo:null,titulo:'Nuevo tema',contenido:null,horas_catedra:null,tiempo_texto:null,fecha_texto:null,indicadores:[{orden:1,codigo:null,descripcion:'Nuevo indicador',check:null}],procedimientos_evaluativos:[],instrumentos_evaluativos:[]}]});});},false));
    body.appendChild(capacities);
    body.appendChild(smallButton('Quitar unidad',function(){mutateList(units,function(){units.splice(index,1);});},true));
    details.appendChild(body); return details;
  }

  function counts(plan) {
    const result = { units:plan.unidades.length, capacities:0, topics:0, indicators:0 };
    plan.unidades.forEach(function (unit) { result.capacities += unit.capacidades.length; unit.capacidades.forEach(function (capacity) { result.topics += capacity.temas.length; capacity.temas.forEach(function (topic) { result.indicators += topic.indicadores.length; }); }); });
    return result;
  }

  function updateStateLabel() {
    const node = document.getElementById('plan-import-save-state');
    if (node) node.textContent = dirty ? 'Hay correcciones sin guardar.' : 'Correcciones guardadas en staging.';
  }

  function decisionValue(suggestion) {
    const saved=(preview.decisions||[]).find(function(d){return d.kind===suggestion.kind&&d.text===suggestion.text;});
    if(saved)return saved.action==='existing'?'existing:'+saved.id:saved.action;
    return suggestion.suggested_id ? 'existing:'+suggestion.suggested_id : '';
  }

  function collectDecisions() {
    return Array.from(root.querySelectorAll('[data-catalog-decision]')).filter(function(select){return select.value;}).map(function(select){
      const parts=select.value.split(':');
      return {kind:select.dataset.kind,text:select.dataset.text,action:parts[0],id:parts[0]==='existing'?Number(parts[1]):null};
    });
  }

  function renderReview() {
    const section=element('section','plan-import-review');
    const source=preview.plan.source, confidence=preview.plan.confidence;
    section.appendChild(element('h3','text-xl font-bold mb-3','Detección y revisión'));
    section.appendChild(element('p','font-bold','Formato detectado: '+source.format));
    section.appendChild(element('p','text-sm mt-1','Confianza = consistencia de reglas deterministas cumplidas; no es una probabilidad.'));
    const confidenceGrid=element('div','plan-import-confidence');
    [['Global',confidence.global],['Metadata',confidence.metadata],['Estructura',confidence.estructura],['Asociaciones',confidence.asociaciones]].forEach(function(item){confidenceGrid.appendChild(element('div','plan-import-confidence-card',item[0]+': '+item[1]+'/100'));});
    section.appendChild(confidenceGrid);
    const warningTitle=element('h4','font-bold mt-4','Advertencias ('+preview.plan.warnings.length+')');section.appendChild(warningTitle);
    const warnings=element('div','plan-import-warnings');
    if(!preview.plan.warnings.length)warnings.appendChild(element('p','app-message-success','No hay advertencias del parser.'));
    preview.plan.warnings.forEach(function(warning){
      const issue=(preview.readiness.issues||[]).find(function(item){return item.code===warning.type;});
      const card=element('article',issue&&issue.blocking?'app-message-error':'plan-import-warning');
      card.appendChild(element('strong','',warning.type+(issue&&issue.blocking?' · BLOQUEANTE':' · REVISAR')));
      card.appendChild(element('p','',warning.message));
      if(warning.page||warning.line)card.appendChild(element('small','','Página '+(warning.page||'?')+' · línea '+(warning.line||'?')));
      if(warning.text)card.appendChild(element('pre','plan-import-warning-text',warning.text));
      warnings.appendChild(card);
    });section.appendChild(warnings);
    const special=(preview.readiness.issues||[]).filter(function(i){return !preview.plan.warnings.some(function(w){return w.type===i.code;});});
    special.forEach(function(issue){section.appendChild(element('p',issue.blocking?'app-message-error':'plan-import-warning',issue.code+': '+issue.message));});

    const catalog=element('div','plan-import-catalog');catalog.appendChild(element('h4','font-bold mt-4 mb-2','Procedimientos e instrumentos'));
    if(!preview.suggestions.length)catalog.appendChild(element('p','text-sm','No hay textos de catálogo para reconciliar.'));
    preview.suggestions.forEach(function(suggestion){
      const label=element('label','font-semibold plan-import-catalog-row');label.appendChild(element('span','',suggestion.kind==='procedimientos'?'Procedimiento: '+suggestion.text:'Instrumento: '+suggestion.text));
      const select=document.createElement('select');select.className='app-input';select.dataset.catalogDecision='1';select.dataset.kind=suggestion.kind;select.dataset.text=suggestion.text;
      select.appendChild(new Option('Seleccione una decisión',''));
      (preview.catalogs[suggestion.kind]||[]).forEach(function(entry){select.appendChild(new Option('Usar existente: '+entry.nombre,'existing:'+entry.id));});
      select.appendChild(new Option('Crear al confirmar','create'));select.appendChild(new Option('Excluir de la importación','exclude'));select.value=decisionValue(suggestion);
      select.addEventListener('change',function(){dirty=true;updateStateLabel();});label.appendChild(select);catalog.appendChild(label);
    });section.appendChild(catalog);
    return section;
  }

  async function savePreview(plan) {
    if ((preview.programming||[]).some(row=>!row.fecha_inicio||!row.fecha_fin)) throw new Error('Seleccione inicio y fin para todas las programaciones agregadas.');
    preview.decisions=collectDecisions();
    preview = await request(config.apiPreview, { method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-Token':config.csrf}, body:JSON.stringify({token:config.token,plan:plan,decisions:preview.decisions,programming:preview.programming||[]}) });
    dirty=false;render();
  }

  function confirmationSummary(plan) {
    const total=counts(plan), context=preview.context||{};
    return 'Confirme la importación:\n\nAsignación: '+[context.materia,context.grado,context.aula].filter(Boolean).join(' · ')+'\nAño: '+preview.year+'\nUnidades: '+total.units+'\nCapacidades: '+total.capacities+'\nTemas: '+total.topics+'\nIndicadores: '+total.indicators+'\nWarnings pendientes: '+plan.warnings.length+'\n\nEl plan se creará en estado BORRADOR.';
  }

  function render() {
    root.replaceChildren();
    const plan = preview.plan;
    const counter = counts(plan);
    const top = element('div', 'plan-import-toolbar');
    top.appendChild(element('div', 'plan-import-counts', counter.units+' unidades · '+counter.capacities+' capacidades · '+counter.topics+' temas · '+counter.indicators+' indicadores'));
    const expandButtons = element('div', 'flex gap-2');
    const expand = element('button', 'btn btn-secondary', 'Expandir todo'); expand.type='button';
    const collapse = element('button', 'btn btn-secondary', 'Contraer todo'); collapse.type='button';
    expand.addEventListener('click', function(){ root.querySelectorAll('details').forEach(function(node){node.open=true;}); });
    collapse.addEventListener('click', function(){ root.querySelectorAll('details').forEach(function(node){node.open=false;}); });
    expandButtons.append(expand, collapse); top.appendChild(expandButtons); root.appendChild(top);

    root.appendChild(renderReview());

    const general = element('section', 'plan-import-section');
    general.appendChild(element('h3', 'text-xl font-bold mb-3', 'Datos generales'));
    const grid = element('div', 'app-form-grid');
    [['Institución fuente','institucion'],['Materia fuente','materia'],['Profesor fuente','profesor'],['Curso fuente','curso'],['Turno fuente','turno'],['Año fuente','anio'],['Días de clase','dias_clase'],['Competencia general','competencia_general'],['Competencia específica','competencia_especifica']].forEach(function(pair){
      const long = ['dias_clase','competencia_general','competencia_especifica'].includes(pair[1]);
      grid.appendChild(field(pair[0], plan.metadata, pair[1], { type:pair[1]==='anio'?'number':undefined, min:'2000', multiline:long, wide:long }));
    });
    general.appendChild(grid); root.appendChild(general);

    const hierarchy = element('section', 'plan-import-section');
    hierarchy.appendChild(element('h3', 'text-xl font-bold mb-3', 'Estructura del plan'));
    plan.unidades.forEach(function(unit,index){hierarchy.appendChild(renderUnit(unit,index,plan.unidades));});
    hierarchy.appendChild(smallButton('Agregar unidad',function(){mutateList(plan.unidades,function(){plan.unidades.push({orden:plan.unidades.length+1,codigo:null,nombre:'Nueva unidad',descripcion:null,horas_catedra:null,tiempo_texto:null,proceso_texto:null,area_transversal:null,metodologia:null,medios_verificacion:null,capacidades:[{orden:1,descripcion:'Nueva capacidad',proceso_desarrollo:null,temas:[{orden:1,codigo:null,titulo:'Nuevo tema',contenido:null,horas_catedra:null,tiempo_texto:null,fecha_texto:null,indicadores:[{orden:1,codigo:null,descripcion:'Nuevo indicador',check:null}],procedimientos_evaluativos:[],instrumentos_evaluativos:[]}]}]});});},false));
    root.appendChild(hierarchy);

    const actions = element('div', 'plan-import-actions');
    const state = element('p', 'text-sm', 'Correcciones guardadas en staging.'); state.id='plan-import-save-state'; actions.appendChild(state);
    const save = element('button', 'btn btn-success', 'Guardar correcciones'); save.type='button';
    save.addEventListener('click', async function(){
      save.disabled=true; save.textContent='Guardando...';
      try {
        await savePreview(plan);
      } catch(error) { state.textContent=error.message; state.className='app-message-error'; }
      finally { save.disabled=false; save.textContent='Guardar correcciones'; }
    });
    const buttons=element('div','flex flex-wrap gap-2');
    const cancel=element('button','btn btn-danger','Cancelar importación');cancel.type='button';
    cancel.addEventListener('click',async function(){
      if(!window.confirm('Se eliminará este staging y sus correcciones. ¿Cancelar la importación?'))return;
      cancel.disabled=true;
      try{await request(config.apiCancel,{method:'DELETE',headers:{'Content-Type':'application/json','X-CSRF-Token':config.csrf},body:JSON.stringify({token:config.token})});window.location.assign(config.planning);}
      catch(error){state.textContent=error.message;state.className='app-message-error';cancel.disabled=false;}
    });
    const confirmButton=element('button','btn btn-success','Confirmar e importar');confirmButton.type='button';confirmButton.disabled=!preview.readiness.can_confirm;
    confirmButton.title=preview.readiness.can_confirm?'':'Resuelva primero todos los bloqueos del preview.';
    confirmButton.addEventListener('click',async function(){
      if(confirming)return;confirming=true;confirmButton.disabled=true;
      try{
        if(dirty)await savePreview(plan);
        if(!preview.readiness.can_confirm)throw new Error('Existen advertencias o decisiones bloqueantes.');
        if(!window.confirm(confirmationSummary(preview.plan))){confirming=false;render();return;}
        const result=await request(config.apiConfirm,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':config.csrf},body:JSON.stringify({token:config.token})});
        window.location.assign(config.editor+'?id_plan='+encodeURIComponent(result.id_plan));
      }catch(error){confirming=false;render();const next=document.getElementById('plan-import-save-state');if(next){next.textContent=error.message;next.className='app-message-error';}}
    });
    buttons.append(cancel,save,confirmButton);actions.appendChild(buttons);root.appendChild(actions);
  }

  request(config.apiPreview+'?token='+encodeURIComponent(config.token)).then(function(data){ preview=data; render(); }).catch(function(error){
    root.replaceChildren(element('p','app-message-error',error.message));
    const back=element('a','btn btn-primary mt-4','Volver a Planificación');back.href=config.planning;root.appendChild(back);
  });
})();
