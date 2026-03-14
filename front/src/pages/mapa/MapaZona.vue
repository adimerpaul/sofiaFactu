<template>
  <q-page class="mapa-zona-page q-pa-sm">
    <div class="row">
      <div class="col-12 col-lg-7">
        <q-card flat bordered class="full-height map-card">
          <q-card-section class="row items-center q-col-gutter-sm q-py-sm q-px-sm toolbar-compact">
            <div class="col-auto">
              <div class="text-subtitle2 text-weight-bold">Mapa zona</div>
            </div>
            <div class="col-auto">
              <q-btn-toggle
                v-model="tipoActivo"
                unelevated
                no-caps
                toggle-color="primary"
                color="grey-4"
                text-color="black"
                :options="tipoOpciones"
              />
            </div>
            <div class="col-grow text-right">
              <q-btn dense color="primary" icon="add_location_alt" no-caps label="Nuevo" @click="startCreate" class="q-mr-sm" />
              <q-btn dense color="grey-8" icon="refresh" no-caps label="Actualizar" :loading="loading" @click="loadData" />
            </div>
          </q-card-section>
          <q-separator />
          <q-card-section class="q-pa-none">
            <div ref="mapRef" class="mapa-zona-map" />
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-5">
        <div class="column">
          <q-card flat bordered>
            <q-card-section class="q-pb-sm">
              <div class="text-subtitle1 text-weight-bold">{{ form.id ? 'Editar poligono' : 'Nuevo poligono' }}</div>
              <div class="text-caption text-grey-7">Tipo {{ tipoActivo }}. Haz clic en el mapa para agregar puntos.</div>
            </q-card-section>
            <q-card-section class="q-pt-none">
              <q-form @submit.prevent="savePoligono">
                <q-input v-model="form.nombre" dense outlined label="Nombre" :rules="[v => !!v || 'Requerido']" />
                <div class="row q-col-gutter-sm q-mt-sm">
                  <div class="col-4">
                    <q-select v-model="form.tipo" :options="tipoOpciones" dense outlined emit-value map-options label="Tipo" />
                  </div>
                  <div class="col-8">
                    <q-input v-model="form.color" dense outlined label="Color" hint="#ff0000" />
                  </div>
                </div>
                <div class="row q-col-gutter-sm q-mt-sm">
                  <div class="col-7">
                    <q-select v-model="form.pedido_zona_id" :options="pedidoZonasOptions" dense outlined emit-value map-options clearable label="Zona de pedido" />
                  </div>
                  <div class="col-5">
                    <q-input v-model.number="form.orden" dense outlined type="number" label="Orden" />
                  </div>
                </div>
                <div class="row q-col-gutter-sm q-mt-sm items-center">
                  <div class="col-6">
                    <q-toggle v-model="form.activo" label="Activo" color="primary" />
                  </div>
                  <div class="col-6 text-right">
                    <q-chip dense :style="{ backgroundColor: form.color || '#607d8b', color: textColor(form.color || '#607d8b') }">
                      {{ form.nombre || 'Vista previa' }}
                    </q-chip>
                  </div>
                </div>
                <div class="row q-col-gutter-sm q-mt-sm">
                  <div class="col-6">
                    <q-btn dense color="warning" no-caps icon="undo" label="Deshacer" class="full-width" :disable="form.coordenadas.length === 0" @click="undoPoint" />
                  </div>
                  <div class="col-6">
                    <q-btn dense color="negative" no-caps icon="delete_sweep" label="Limpiar" class="full-width" :disable="form.coordenadas.length === 0" @click="clearPoints" />
                  </div>
                </div>
                <div class="text-caption text-grey-7 q-mt-sm q-mb-xs">Vertices: {{ form.coordenadas.length }}</div>
                <div class="points-list compact-points">
                  <div v-for="(point, index) in form.coordenadas" :key="index" class="point-row">
                    <q-input v-model.number="point.lat" dense outlined type="number" step="0.000001" label="Lat" @update:model-value="renderEditor" />
                    <q-input v-model.number="point.lng" dense outlined type="number" step="0.000001" label="Lng" @update:model-value="renderEditor" />
                    <q-btn dense flat round color="negative" icon="delete" @click="removePoint(index)" />
                  </div>
                </div>
                <div class="text-right q-mt-md">
                  <q-btn flat no-caps color="grey-8" label="Nuevo" @click="startCreate" />
                  <q-btn color="primary" no-caps :label="form.id ? 'Actualizar' : 'Guardar'" type="submit" :loading="saving" class="q-ml-sm" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>

          <q-card flat bordered class="saved-card">
            <q-card-section class="row items-center q-col-gutter-sm q-pb-sm">
              <div class="col">
                <div class="text-subtitle1 text-weight-bold">Poligonos guardados</div>
                <div class="text-caption text-grey-7">Tipo {{ tipoActivo }}: {{ poligonosFiltrados.length }} registros</div>
              </div>
              <div class="col-12 q-mt-sm">
                <q-input v-model="filter" dense outlined label="Buscar poligono">
                  <template #append><q-icon name="search" /></template>
                </q-input>
              </div>
            </q-card-section>
            <q-separator />
            <q-list separator class="saved-list">
              <q-item v-for="poligono in poligonosFiltrados" :key="poligono.id" clickable @click="editPoligono(poligono)">
                <q-item-section avatar>
                  <div class="polygon-color" :style="{ backgroundColor: poligono.color || '#607d8b' }" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ poligono.nombre }}</q-item-label>
                  <q-item-label caption>
                    Tipo {{ poligono.tipo }} | {{ poligono.pedido_zona?.nombre || 'Sin zona' }} | {{ poligono.coordenadas?.length || 0 }} puntos
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row no-wrap items-center">
                    <q-chip dense :color="poligono.activo ? 'green-7' : 'grey-6'" text-color="white">
                      {{ poligono.activo ? 'Activo' : 'Inactivo' }}
                    </q-chip>
                    <q-btn dense flat round color="negative" icon="delete" @click.stop="removePoligono(poligono)" />
                  </div>
                </q-item-section>
              </q-item>
              <q-item v-if="poligonosFiltrados.length === 0">
                <q-item-section class="text-grey-6">Sin registros para este tipo</q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const { proxy } = getCurrentInstance()
const ORURO_CENTER = [-17.969721, -67.114493]

const mapRef = ref(null)
const map = ref(null)
const polygonsLayer = ref(null)
const editorPolygonLayer = ref(null)
const editorMarkerLayer = ref(null)

const loading = ref(false)
const saving = ref(false)
const filter = ref('')
const tipoActivo = ref(5)

const poligonos = ref([])
const pedidoZonas = ref([])
const selectedPolygonId = ref(null)

const tipoOpciones = [
  { label: '5', value: 5 },
  { label: '4', value: 4 },
  { label: '3', value: 3 },
]

const form = ref(createEmptyForm())

function createEmptyForm () {
  return {
    id: null,
    nombre: '',
    tipo: tipoActivo.value,
    color: defaultColorByTipo(tipoActivo.value),
    pedido_zona_id: null,
    orden: 0,
    activo: true,
    coordenadas: [],
  }
}

const pedidoZonasOptions = computed(() => [
  { label: 'Sin zona', value: null },
  ...pedidoZonas.value.map(zona => ({ label: zona.nombre, value: zona.id })),
])

const poligonosFiltrados = computed(() => {
  const needle = filter.value.trim().toLowerCase()
  const base = poligonos.value.filter(poligono => Number(poligono.tipo) === Number(tipoActivo.value))
  if (!needle) return base
  return base.filter((poligono) => {
    const stack = `${poligono.nombre || ''} ${poligono.pedido_zona?.nombre || ''}`.toLowerCase()
    return stack.includes(needle)
  })
})

function defaultColorByTipo (tipo) {
  if (Number(tipo) === 5) return '#ff0000'
  if (Number(tipo) === 4) return '#00FF00'
  return '#1f77b4'
}

function textColor (bg) {
  const hex = String(bg || '').replace('#', '')
  if (hex.length !== 6) return '#ffffff'
  const r = parseInt(hex.slice(0, 2), 16)
  const g = parseInt(hex.slice(2, 4), 16)
  const b = parseInt(hex.slice(4, 6), 16)
  const yiq = (r * 299 + g * 587 + b * 114) / 1000
  return yiq >= 140 ? '#111827' : '#ffffff'
}

function roundCoord (value) {
  return Math.round(Number(value) * 1000000) / 1000000
}

function initMap () {
  if (!mapRef.value || map.value) return

  map.value = L.map(mapRef.value, { center: ORURO_CENTER, zoom: 13 })
  const googleRoad = L.tileLayer('https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}', { maxZoom: 21, attribution: 'Map data � Google' })
  const googleSat = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', { maxZoom: 21, attribution: 'Map data � Google' })
  const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { maxZoom: 21, attribution: 'Map data � Google' })
  const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' })

  googleRoad.addTo(map.value)
  L.control.layers({
    'Google Calles': googleRoad,
    'Google Satelital': googleSat,
    'Google Hibrido': googleHybrid,
    OpenStreetMap: osm,
  }).addTo(map.value)

  polygonsLayer.value = L.layerGroup().addTo(map.value)
  editorPolygonLayer.value = L.layerGroup().addTo(map.value)
  editorMarkerLayer.value = L.layerGroup().addTo(map.value)

  map.value.on('click', (event) => {
    form.value.coordenadas.push({ lat: roundCoord(event.latlng.lat), lng: roundCoord(event.latlng.lng) })
    renderEditor()
  })
}

function renderSavedPolygons () {
  if (!polygonsLayer.value) return
  polygonsLayer.value.clearLayers()

  const bounds = []
  poligonosFiltrados.value.forEach((poligono) => {
    const latlngs = Array.isArray(poligono.coordenadas)
      ? poligono.coordenadas.map(point => [Number(point.lat), Number(point.lng)]).filter(([lat, lng]) => Number.isFinite(lat) && Number.isFinite(lng))
      : []

    if (latlngs.length < 3) return
    latlngs.forEach(point => bounds.push(point))

    const color = poligono.color || '#607d8b'
    const layer = L.polygon(latlngs, {
      color,
      fillColor: color,
      fillOpacity: selectedPolygonId.value === poligono.id ? 0.34 : 0.18,
      weight: selectedPolygonId.value === poligono.id ? 4 : 2,
    }).addTo(polygonsLayer.value)

    layer.bindTooltip(`<div style="font-size:12px;"><b>${poligono.nombre}</b><br>Tipo ${poligono.tipo}</div>`, { sticky: true })
    layer.on('click', () => editPoligono(poligono))
  })

  if (bounds.length > 0) {
    map.value.fitBounds(bounds, { padding: [24, 24], maxZoom: 15 })
  }
}

function renderEditor () {
  if (!editorPolygonLayer.value || !editorMarkerLayer.value) return

  editorPolygonLayer.value.clearLayers()
  editorMarkerLayer.value.clearLayers()

  const latlngs = form.value.coordenadas.map(point => [Number(point.lat), Number(point.lng)]).filter(([lat, lng]) => Number.isFinite(lat) && Number.isFinite(lng))

  if (latlngs.length >= 2) {
    L.polyline(latlngs, { color: form.value.color || defaultColorByTipo(form.value.tipo), weight: 3, dashArray: '6 4' }).addTo(editorPolygonLayer.value)
  }

  if (latlngs.length >= 3) {
    L.polygon(latlngs, { color: form.value.color || defaultColorByTipo(form.value.tipo), fillColor: form.value.color || defaultColorByTipo(form.value.tipo), fillOpacity: 0.24, weight: 3 }).addTo(editorPolygonLayer.value)
  }

  form.value.coordenadas.forEach((point, index) => {
    const lat = Number(point.lat)
    const lng = Number(point.lng)
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return

    const marker = L.marker([lat, lng], {
      draggable: true,
      icon: L.divIcon({
        className: '',
        html: `<div class="vertex-marker">${index + 1}</div>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
      }),
    }).addTo(editorMarkerLayer.value)

    marker.on('drag', (event) => {
      const { latlng } = event.target
      form.value.coordenadas[index].lat = roundCoord(latlng.lat)
      form.value.coordenadas[index].lng = roundCoord(latlng.lng)
      renderEditor()
    })

    marker.on('dblclick', () => removePoint(index))
  })
}

function startCreate () {
  selectedPolygonId.value = null
  form.value = createEmptyForm()
  renderSavedPolygons()
  renderEditor()
}

function editPoligono (poligono) {
  selectedPolygonId.value = poligono.id
  tipoActivo.value = Number(poligono.tipo)
  form.value = {
    id: poligono.id,
    nombre: poligono.nombre,
    tipo: Number(poligono.tipo),
    color: poligono.color || defaultColorByTipo(poligono.tipo),
    pedido_zona_id: poligono.pedido_zona_id,
    orden: Number(poligono.orden || 0),
    activo: !!poligono.activo,
    coordenadas: Array.isArray(poligono.coordenadas) ? poligono.coordenadas.map(point => ({ lat: Number(point.lat), lng: Number(point.lng) })) : [],
  }
  renderSavedPolygons()
  renderEditor()
}

function undoPoint () {
  form.value.coordenadas.pop()
  renderEditor()
}

function clearPoints () {
  form.value.coordenadas = []
  renderEditor()
}

function removePoint (index) {
  form.value.coordenadas.splice(index, 1)
  renderEditor()
}

async function loadData () {
  loading.value = true
  try {
    const [mapaRes, pedidoZonasRes] = await Promise.all([
      proxy.$axios.get('/mapa-zona'),
      proxy.$axios.get('/pedido-zonas'),
    ])
    poligonos.value = Array.isArray(mapaRes.data?.poligonos) ? mapaRes.data.poligonos : []
    pedidoZonas.value = Array.isArray(pedidoZonasRes.data) ? pedidoZonasRes.data : []
    renderSavedPolygons()
    renderEditor()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo cargar mapa zona')
  } finally {
    loading.value = false
  }
}

async function savePoligono () {
  if (form.value.coordenadas.length < 3) {
    proxy.$alert.error('Debes registrar al menos 3 puntos para el poligono')
    return
  }

  saving.value = true
  try {
    const payload = {
      nombre: form.value.nombre,
      tipo: Number(form.value.tipo),
      color: form.value.color || defaultColorByTipo(form.value.tipo),
      pedido_zona_id: form.value.pedido_zona_id,
      orden: Number(form.value.orden || 0),
      activo: !!form.value.activo,
      coordenadas: form.value.coordenadas.map(point => ({ lat: roundCoord(point.lat), lng: roundCoord(point.lng) })),
    }

    if (form.value.id) {
      await proxy.$axios.put(`/mapa-zona/poligonos/${form.value.id}`, payload)
      proxy.$alert.success('Poligono actualizado')
    } else {
      await proxy.$axios.post('/mapa-zona/poligonos', payload)
      proxy.$alert.success('Poligono creado')
    }

    await loadData()
    startCreate()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo guardar el poligono')
  } finally {
    saving.value = false
  }
}

function removePoligono (poligono) {
  proxy.$alert.dialog(`Desea eliminar el poligono ${poligono.nombre}?`).onOk(async () => {
    try {
      await proxy.$axios.delete(`/mapa-zona/poligonos/${poligono.id}`)
      proxy.$alert.success('Poligono eliminado')
      if (selectedPolygonId.value === poligono.id) startCreate()
      await loadData()
    } catch (e) {
      proxy.$alert.error(e?.response?.data?.message || 'No se pudo eliminar el poligono')
    }
  })
}

watch(tipoActivo, (tipo) => {
  if (!form.value.id) {
    form.value.tipo = Number(tipo)
    if (!form.value.color) form.value.color = defaultColorByTipo(tipo)
  }
  renderSavedPolygons()
})

onMounted(() => {
  initMap()
  loadData()
})

onBeforeUnmount(() => {
  if (map.value) {
    map.value.remove()
    map.value = null
  }
})
</script>

<style scoped>
.mapa-zona-page {
  background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
}

.mapa-zona-map {
  height: 78vh;
  min-height: 640px;
}

.toolbar-compact {
  min-height: 56px;
}

.side-column {
  height: 100%;
}

.saved-card {
  flex: 1;
}

.saved-list {
  max-height: 48vh;
  overflow: auto;
}

.points-list {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px;
  background: #f8fafc;
}

.compact-points {
  max-height: 180px;
  overflow: auto;
}

.point-row {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 8px;
  align-items: center;
  margin-bottom: 8px;
}

.polygon-color {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.15);
}

:deep(.vertex-marker) {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #0f172a;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  border: 2px solid #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
}
</style>
