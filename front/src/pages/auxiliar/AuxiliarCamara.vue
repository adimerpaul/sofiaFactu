<template>
  <q-page class="q-pa-sm auxiliar-page">
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-12 col-md-2">
          <q-input v-model="fecha" dense outlined type="date" label="Fecha" />
        </div>
        <div class="col-12 col-md-3">
          <q-btn color="primary" icon="cloud_download" label="Importar pedidos (offline)" no-caps class="full-width" :loading="loading" @click="importarPedidos" />
        </div>
        <div class="col-12 col-md-2">
          <q-btn color="grey-8" icon="storage" label="Cargar offline" no-caps class="full-width" @click="cargarOffline" />
        </div>
        <div class="col-12 col-md-2">
          <q-btn color="indigo" icon="print" label="PDF pedidos" no-caps class="full-width" :loading="loadingReport" @click="reportePedidos" />
        </div>
        <div class="col-12 col-md-3">
          <q-btn color="deep-orange" icon="inventory_2" label="PDF productos totales" no-caps class="full-width" :loading="loadingReport" @click="reporteProductosTotales" />
        </div>
      </q-card-section>
      <q-card-section class="q-pt-none text-caption text-grey-7">
        Ultima sincronizacion: {{ lastSync || 'sin datos' }} | Fuente: {{ sourceLabel }}
      </q-card-section>
      <q-card-section class="row q-col-gutter-sm q-pt-none">
        <div class="col-6 col-md-2"><q-chip color="blue-8" text-color="white">Pedidos: {{ stats.total_pedidos || 0 }}</q-chip></div>
        <div class="col-6 col-md-2"><q-chip color="teal-7" text-color="white">Bs: {{ Number(stats.total_bs || 0).toFixed(2) }}</q-chip></div>
        <div class="col-6 col-md-2"><q-chip color="orange-8" text-color="white">Pendientes: {{ stats.pendientes || 0 }}</q-chip></div>
        <div class="col-6 col-md-2"><q-chip color="green-8" text-color="white">Hechos: {{ stats.hechos || 0 }}</q-chip></div>
        <div class="col-6 col-md-2"><q-chip color="purple-7" text-color="white">Modificados: {{ stats.modificados || 0 }}</q-chip></div>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row q-col-gutter-sm">
        <div class="col-12 col-md-2">
          <q-select v-model="tipo" :options="tipoOptions" dense outlined emit-value map-options label="Tipo" />
        </div>
        <div class="col-12 col-md-2">
          <q-select v-model="auxiliarEstado" :options="auxEstadoOptions" dense outlined emit-value map-options label="Estado auxiliar" />
        </div>
        <div class="col-12 col-md-2">
          <q-select v-model="clienteId" :options="clienteOptions" dense outlined emit-value map-options label="Cliente" />
        </div>
        <div class="col-12 col-md-2">
          <q-select v-model="vendedorId" :options="vendedorOptions" dense outlined emit-value map-options label="Vendedor" />
        </div>
        <div class="col-12 col-md-2">
          <q-select v-model="camionId" :options="camionOptions" dense outlined emit-value map-options label="Camion" />
        </div>
        <div class="col-12 col-md-2">
          <q-select v-model="zonaId" :options="zonaOptions" dense outlined emit-value map-options label="Zona" />
        </div>
        <div class="col-12 col-md-4">
          <q-input v-model="search" dense outlined debounce="300" label="Buscar cliente / direccion / pedido">
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered>
      <q-card-section class="text-subtitle2">
        Pedidos filtrados: {{ filteredRows.length }}
      </q-card-section>
      <q-separator />
      <q-list separator>
        <q-expansion-item
          v-for="pedido in filteredRows"
          :key="pedido.id"
          :label="`#${pedido.id} - ${pedido.cliente?.nombre || 'SIN CLIENTE'}`"
          :caption="`${pedido.vendedor?.name || '-'} | ${pedido.camion?.name || 'SIN CAMION'} | ${pedido.zona?.nombre || 'SIN ZONA'}`"
          expand-separator
          dense
        >
          <q-card flat>
            <q-card-section class="row q-col-gutter-sm">
              <div class="col-12 col-md-2">
                <q-chip :color="chipEstadoColor(pedido.auxiliar_estado)" text-color="white">
                  {{ pedido.auxiliar_estado || 'PENDIENTE' }}
                </q-chip>
              </div>
              <div class="col-12 col-md-2 text-weight-bold">
                Total: {{ Number(pedido.total || 0).toFixed(2) }} Bs
              </div>
              <div class="col-12 col-md-4 text-grey-8">
                {{ pedido.cliente?.direccion || '-' }}
              </div>
              <div class="col-12 col-md-4 text-grey-8">
                Venta: {{ pedido.venta_id ? `#${pedido.venta_id}` : 'No generada' }}
              </div>
            </q-card-section>

            <q-table
              dense
              flat
              bordered
              :rows="pedido.detalles || []"
              :columns="detalleColumns"
              row-key="id"
              :pagination="{ rowsPerPage: 0 }"
            >
              <template #body-cell-imagen="props">
                <q-td :props="props">
                  <q-avatar size="30px" rounded>
                    <q-img v-if="props.row.imagen" :src="imgUrl(props.row.imagen)" />
                    <span v-else>-</span>
                  </q-avatar>
                </q-td>
              </template>
              <template #body-cell-cantidad="props">
                <q-td :props="props">
                  <q-input
                    :model-value="getEditCantidad(pedido.id, props.row)"
                    type="number"
                    dense
                    outlined
                    step="0.01"
                    min="0"
                    @update:model-value="(v) => setEditCantidad(pedido.id, props.row.id, v)"
                  />
                </q-td>
              </template>
            </q-table>

            <q-card-section class="row q-col-gutter-sm items-center">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="observacionesAux[pedido.id]"
                  dense
                  outlined
                  label="Observacion auxiliar"
                  maxlength="600"
                />
              </div>
              <div class="col-12 col-md-3">
                <q-btn color="amber-9" text-color="black" icon="edit" label="Guardar modificado" no-caps class="full-width" :loading="processingId === pedido.id" @click="guardarModificado(pedido)" />
              </div>
              <div class="col-12 col-md-3">
                <q-btn color="green" icon="check_circle" label="Hecho + generar venta" no-caps class="full-width" :loading="processingId === pedido.id" :disable="pedido.venta_generada" @click="marcarHecho(pedido)" />
              </div>
            </q-card-section>
          </q-card>
        </q-expansion-item>
      </q-list>
    </q-card>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'

const { proxy } = getCurrentInstance()

const fecha = ref(new Date().toISOString().slice(0, 10))
const loading = ref(false)
const loadingReport = ref(false)
const processingId = ref(null)
const sourceLabel = ref('API')
const lastSync = ref('')

const rows = ref([])
const stats = ref({})
const editCantidad = ref({})
const observacionesAux = ref({})

const search = ref('')
const tipo = ref('TODOS')
const auxiliarEstado = ref('TODOS')
const clienteId = ref(null)
const vendedorId = ref(null)
const camionId = ref(null)
const zonaId = ref(null)

const tipoOptions = [
  { label: 'Todos', value: 'TODOS' },
  { label: 'Normal', value: 'NORMAL' },
  { label: 'Pollo', value: 'POLLO' },
  { label: 'Res', value: 'RES' },
  { label: 'Cerdo', value: 'CERDO' },
]
const auxEstadoOptions = [
  { label: 'Todos', value: 'TODOS' },
  { label: 'Pendiente', value: 'PENDIENTE' },
  { label: 'Hecho', value: 'HECHO' },
  { label: 'Modificado', value: 'MODIFICADO' },
]

const detalleColumns = [
  { name: 'imagen', label: 'Img', field: 'imagen', align: 'left' },
  { name: 'codigo', label: 'Codigo', field: 'codigo', align: 'left' },
  { name: 'producto', label: 'Producto', field: 'producto', align: 'left' },
  { name: 'tipo', label: 'Tipo', field: 'tipo', align: 'left' },
  { name: 'cantidad', label: 'Cantidad', field: 'cantidad', align: 'left' },
  { name: 'precio', label: 'Precio', field: row => Number(row.precio || 0).toFixed(2), align: 'right' },
  { name: 'total', label: 'Subtotal', field: row => Number(row.total || 0).toFixed(2), align: 'right' },
]

const clienteOptions = computed(() => {
  const map = new Map()
  rows.value.forEach((r) => {
    if (r?.cliente?.id) map.set(r.cliente.id, r.cliente.nombre || `Cliente ${r.cliente.id}`)
  })
  return [{ label: 'Todos', value: null }, ...Array.from(map.entries()).map(([id, label]) => ({ label, value: id }))]
})

const vendedorOptions = computed(() => {
  const map = new Map()
  rows.value.forEach((r) => {
    if (r?.vendedor?.id) map.set(r.vendedor.id, r.vendedor.name || `Vendedor ${r.vendedor.id}`)
  })
  return [{ label: 'Todos', value: null }, ...Array.from(map.entries()).map(([id, label]) => ({ label, value: id }))]
})

const camionOptions = computed(() => {
  const map = new Map()
  rows.value.forEach((r) => {
    if (r?.camion?.id) {
      const extra = r.camion.placa ? ` (${r.camion.placa})` : ''
      map.set(r.camion.id, `${r.camion.name || `Camion ${r.camion.id}`}${extra}`)
    }
  })
  return [{ label: 'Todos', value: null }, ...Array.from(map.entries()).map(([id, label]) => ({ label, value: id }))]
})

const zonaOptions = computed(() => {
  const map = new Map()
  rows.value.forEach((r) => {
    if (r?.zona?.id) map.set(r.zona.id, r.zona.nombre || `Zona ${r.zona.id}`)
  })
  return [{ label: 'Todas', value: null }, ...Array.from(map.entries()).map(([id, label]) => ({ label, value: id }))]
})

const filteredRows = computed(() => {
  const needle = (search.value || '').trim().toLowerCase()
  return rows.value.filter((r) => {
    if (clienteId.value && r?.cliente?.id !== clienteId.value) return false
    if (vendedorId.value && r?.vendedor?.id !== vendedorId.value) return false
    if (camionId.value && r?.camion?.id !== camionId.value) return false
    if (zonaId.value && r?.zona?.id !== zonaId.value) return false
    if (auxiliarEstado.value !== 'TODOS' && (r.auxiliar_estado || 'PENDIENTE') !== auxiliarEstado.value) return false
    if (tipo.value !== 'TODOS') {
      if (tipo.value === 'NORMAL' && !r.contiene_normal) return false
      if (tipo.value === 'POLLO' && !r.contiene_pollo) return false
      if (tipo.value === 'RES' && !r.contiene_res) return false
      if (tipo.value === 'CERDO' && !r.contiene_cerdo) return false
    }
    if (!needle) return true
    const stack = [
      r.id,
      r?.cliente?.nombre,
      r?.cliente?.codcli,
      r?.cliente?.ci,
      r?.cliente?.direccion,
      r?.vendedor?.name,
      r?.camion?.name,
      r?.zona?.nombre,
    ].join(' ').toLowerCase()
    return stack.includes(needle)
  })
})

function cacheKey () {
  return `auxiliar_camara_${fecha.value}`
}

function imgUrl (path) {
  if (!path) return ''
  const base = String(proxy.$url || '').replace(/\/+$/, '')
  return `${base}/${String(path).replace(/^\/+/, '')}`
}

function chipEstadoColor (estado) {
  if (estado === 'HECHO') return 'green'
  if (estado === 'MODIFICADO') return 'purple'
  return 'orange'
}

function syncEditMaps () {
  const nextQty = {}
  const nextObs = {}
  rows.value.forEach((pedido) => {
    nextQty[pedido.id] = {}
    ;(pedido.detalles || []).forEach((d) => {
      nextQty[pedido.id][d.id] = d.cantidad
    })
    nextObs[pedido.id] = pedido.auxiliar_observacion || ''
  })
  editCantidad.value = nextQty
  observacionesAux.value = nextObs
}

function getEditCantidad (pedidoId, detalle) {
  return editCantidad.value?.[pedidoId]?.[detalle.id] ?? detalle.cantidad
}

function setEditCantidad (pedidoId, detalleId, value) {
  if (!editCantidad.value[pedidoId]) editCantidad.value[pedidoId] = {}
  const num = Number(value)
  editCantidad.value[pedidoId][detalleId] = Number.isFinite(num) && num >= 0 ? num : 0
}

function getRequestFilters () {
  return {
    fecha: fecha.value,
    tipo: tipo.value,
    auxiliar_estado: auxiliarEstado.value,
    cliente_id: clienteId.value,
    vendedor_id: vendedorId.value,
    usuario_camion_id: camionId.value,
    pedido_zona_id: zonaId.value,
    search: search.value || undefined,
  }
}

async function importarPedidos () {
  loading.value = true
  try {
    const res = await proxy.$axios.get('/auxiliar-camara/pedidos', { params: getRequestFilters() })
    rows.value = Array.isArray(res.data?.data) ? res.data.data : []
    stats.value = res.data?.meta || {}
    const meta = {
      rows: rows.value,
      stats: stats.value,
      syncedAt: new Date().toISOString(),
      filters: getRequestFilters(),
    }
    localStorage.setItem(cacheKey(), JSON.stringify(meta))
    sourceLabel.value = 'API'
    lastSync.value = new Date(meta.syncedAt).toLocaleString()
    syncEditMaps()
    proxy.$alert.success('Pedidos importados y guardados offline')
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo importar pedidos')
  } finally {
    loading.value = false
  }
}

function cargarOffline () {
  try {
    const raw = localStorage.getItem(cacheKey())
    if (!raw) {
      proxy.$alert.error('No hay cache offline para la fecha seleccionada')
      return
    }
    const payload = JSON.parse(raw)
    rows.value = Array.isArray(payload?.rows) ? payload.rows : []
    stats.value = payload?.stats || {}
    sourceLabel.value = 'OFFLINE'
    lastSync.value = payload?.syncedAt ? new Date(payload.syncedAt).toLocaleString() : ''
    syncEditMaps()
    proxy.$alert.success('Datos cargados desde offline')
  } catch (_) {
    proxy.$alert.error('Cache offline danado')
  }
}

async function procesarPedido (pedido, generarVenta) {
  processingId.value = pedido.id
  try {
    const detallesPayload = (pedido.detalles || []).map((d) => ({
      id: d.id,
      cantidad: Number(getEditCantidad(pedido.id, d)),
    }))
    await proxy.$axios.put(`/auxiliar-camara/pedidos/${pedido.id}/procesar`, {
      generar_venta: generarVenta,
      auxiliar_observacion: observacionesAux.value[pedido.id] || null,
      detalles: detallesPayload,
    })
    await importarPedidos()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo procesar pedido')
  } finally {
    processingId.value = null
  }
}

async function guardarModificado (pedido) {
  await procesarPedido(pedido, false)
}

async function marcarHecho (pedido) {
  await procesarPedido(pedido, true)
}

async function descargarPdf (url, fileName) {
  loadingReport.value = true
  try {
    const res = await proxy.$axios.get(url, {
      params: getRequestFilters(),
      responseType: 'blob',
    })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const d = res?.headers?.['content-disposition'] || ''
    const m = d.match(/filename="?([^"]+)"?/)
    const finalName = m?.[1] || fileName
    const fileUrl = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = fileUrl
    a.download = finalName
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(fileUrl)
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo generar reporte')
  } finally {
    loadingReport.value = false
  }
}

async function reportePedidos () {
  await descargarPdf('/auxiliar-camara/reportes/pedidos', `auxiliar_pedidos_${fecha.value}.pdf`)
}

async function reporteProductosTotales () {
  await descargarPdf('/auxiliar-camara/reportes/productos-totales', `auxiliar_productos_${fecha.value}.pdf`)
}

watch(fecha, () => {
  rows.value = []
  stats.value = {}
  sourceLabel.value = 'API'
  lastSync.value = ''
})

importarPedidos()
</script>

<style scoped>
.auxiliar-page {
  background: linear-gradient(180deg, #eef4ff 0%, #f8fbff 40%, #ffffff 100%);
}
</style>
