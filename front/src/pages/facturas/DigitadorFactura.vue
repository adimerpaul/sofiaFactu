<template>
  <q-page class="q-pa-sm misped-page">
    <q-card flat bordered class="hero-card q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md-4">
          <div class="text-subtitle2 text-grey-7">Panel administrativo</div>
          <div class="text-h6 text-weight-bold">Digitador factura</div>
        </div>
        <div class="col-12 col-md-2">
          <q-input v-model="fechaInicio" type="date" dense outlined label="Fecha inicio" />
        </div>
        <div class="col-12 col-md-2">
          <q-input v-model="fechaFin" type="date" dense outlined label="Fecha fin" />
        </div>
        <div class="col-12 col-md-2">
          <q-toggle v-model="soloFactura" label="Solo factura" color="primary" />
        </div>
        <div class="col-12 col-md-2">
          <q-btn color="primary" icon="search" no-caps class="full-width" label="Consulta" :loading="loading" @click="cargarVentas" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-6 col-md-2">
          <q-chip square color="blue-8" text-color="white" class="full-width justify-center">Ventas: {{ stats.total_ventas || 0 }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="indigo-8" text-color="white" class="full-width justify-center">Monto Bs: {{ Number(stats.monto_total_ventas || 0).toFixed(2) }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="green-8" text-color="white" class="full-width justify-center">Facturadas: {{ stats.ventas_facturadas || 0 }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="orange-8" text-color="white" class="full-width justify-center">No facturadas: {{ stats.ventas_no_facturadas || 0 }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="deep-orange-9" text-color="white" class="full-width justify-center">Pendientes: {{ stats.pendientes_factura || 0 }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="grey-8" text-color="white" class="full-width justify-center">Sin venta: {{ stats.pedidos_sin_venta || 0 }}</q-chip>
        </div>
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-btn
          color="deep-orange"
          icon="receipt_long"
          label="Generar factura de todos (pendiente)"
          no-caps
          size="lg"
          class="full-width text-weight-bold"
          :loading="loadingFacturar"
          @click="generarFacturaTodos"
        />
      </q-card-section>
    </q-card>

    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-12 col-md-4">
          <q-input v-model="search" dense outlined label="Buscar comanda/cliente/vendedor/producto" debounce="250">
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-md-3">
          <q-chip color="indigo-7" text-color="white">Registros: {{ ventasFiltradas.length }}</q-chip>
        </div>
      </q-card-section>
      <q-markup-table flat dense wrap-cells>
        <thead>
          <tr class="bg-grey-2">
            <th>Opciones</th>
            <th>Comanda</th>
            <th>Vendedor</th>
            <th>Cliente</th>
            <th>Tipo</th>
            <th>Producto</th>
            <th>Fec/Hora</th>
            <th>Pago</th>
            <th>Factura</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="venta in ventasFiltradas" :key="venta.venta_id">
            <td>
              <q-btn-dropdown color="primary" label="Opciones" dense no-caps size="10px">
                <q-item clickable v-close-popup @click="abrirEdicion(venta)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section>Editar</q-item-section>
                </q-item>
              </q-btn-dropdown>
            </td>
            <td>{{ venta.comanda || '-' }}</td>
            <td>{{ venta.vendedor || '-' }}</td>
            <td>{{ venta.cliente || '-' }}</td>
            <td>
              <q-chip
                v-for="tipo in (venta.tipo || [])"
                :key="`${venta.venta_id}-${tipo}`"
                dense
                text-color="white"
                :color="tipoColor(tipo)"
                class="q-mr-xs q-mb-xs"
              >
                {{ tipo }}
              </q-chip>
            </td>
            <td>
              <ul style="padding:0;margin:0;list-style:none;">
                <li v-for="p in (venta.productos || [])" :key="`${venta.venta_id}-${p.id}`" style="font-size:0.9em;border-bottom:1px solid #eee;padding:0;">
                  {{ p.nombre }} x {{ p.cantidad }}
                </li>
              </ul>
            </td>
            <td>{{ venta.fecha }} {{ venta.hora || '' }}</td>
            <td>{{ venta.pago || '-' }}</td>
            <td>{{ venta.facturado ? 'SI' : 'NO' }}</td>
            <td>
              <q-chip dense :color="estadoColor(venta.estado)" text-color="white">{{ venta.estado }}</q-chip>
            </td>
          </tr>
          <tr v-if="ventasFiltradas.length === 0">
            <td colspan="10" class="text-center text-grey-7">Sin datos disponibles</td>
          </tr>
        </tbody>
      </q-markup-table>
    </q-card>

    <q-card flat bordered>
      <q-card-section class="text-subtitle2">
        Pedidos sin venta generada: {{ pedidosSinVenta.length }}
      </q-card-section>
      <q-markup-table flat dense wrap-cells>
        <thead>
          <tr class="bg-grey-2">
            <th>Comanda</th>
            <th>Vendedor</th>
            <th>Cliente</th>
            <th>Fec/Hora</th>
            <th>Factura</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in pedidosSinVenta" :key="`sv-${p.comanda}`">
            <td>{{ p.comanda }}</td>
            <td>{{ p.vendedor || '-' }}</td>
            <td>{{ p.cliente || '-' }}</td>
            <td>{{ p.fecha }} {{ p.hora || '' }}</td>
            <td>{{ p.facturado ? 'SI' : 'NO' }}</td>
            <td>{{ p.estado || '-' }}</td>
          </tr>
          <tr v-if="pedidosSinVenta.length === 0">
            <td colspan="6" class="text-center text-grey-7">Sin pendientes</td>
          </tr>
        </tbody>
      </q-markup-table>
    </q-card>

    <q-dialog v-model="dialogEdit" maximized>
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Editar venta #{{ editForm.venta_id }}</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pt-sm">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-3">
              <q-select v-model="editForm.tipo_pago" :options="tipoPagoVentaOptions" dense outlined emit-value map-options label="Tipo pago" />
            </div>
            <div class="col-12 col-md-2">
              <q-toggle v-model="editForm.facturado" label="Facturado" />
            </div>
            <div class="col-12 col-md-3">
              <q-select v-model="editForm.factura_estado" :options="facturaEstadoOptions" dense outlined emit-value map-options label="Estado factura" />
            </div>
          </div>
          <q-markup-table dense flat bordered class="q-mt-sm">
            <thead>
              <tr>
                <th>Cod</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, i) in editForm.productos" :key="`ed-${d.id}-${i}`">
                <td>{{ d.codigo }}</td>
                <td>{{ d.nombre }}</td>
                <td>{{ d.tipo }}</td>
                <td><input v-model.number="d.cantidad" type="number" step="0.01" min="0" style="width: 80px" /></td>
                <td><input v-model.number="d.precio" type="number" step="0.01" min="0" style="width: 90px" /></td>
                <td>{{ (Number(d.cantidad || 0) * Number(d.precio || 0)).toFixed(2) }}</td>
              </tr>
              <tr v-if="editForm.productos.length === 0">
                <td colspan="6" class="text-grey-7">Sin detalles</td>
              </tr>
            </tbody>
          </q-markup-table>
          <div class="text-h6 q-mt-sm">Total: {{ totalEdit.toFixed(2) }} Bs.</div>
        </q-card-section>
        <q-card-actions align="between" class="q-pa-md">
          <q-btn flat color="negative" label="Cerrar" v-close-popup />
          <q-btn color="green" no-caps icon="save" label="Guardar cambios" :loading="saving" @click="guardarEdicion" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'

const { proxy } = getCurrentInstance()

const today = new Date().toISOString().slice(0, 10)
const fechaInicio = ref(today)
const fechaFin = ref(today)
const loading = ref(false)
const loadingFacturar = ref(false)
const saving = ref(false)
const search = ref('')
const soloFactura = ref(false)

const ventas = ref([])
const pedidosSinVenta = ref([])
const stats = ref({
  total_ventas: 0,
  monto_total_ventas: 0,
  ventas_facturadas: 0,
  ventas_no_facturadas: 0,
  pendientes_factura: 0,
  pedidos_sin_venta: 0,
})

const dialogEdit = ref(false)
const editForm = ref({
  venta_id: null,
  tipo_pago: 'Efectivo',
  facturado: false,
  factura_estado: 'SIN_GESTION',
  productos: [],
})

const tipoPagoVentaOptions = [
  { label: 'Efectivo', value: 'Efectivo' },
  { label: 'QR', value: 'QR' },
  { label: 'Contado', value: 'Contado' },
  { label: 'Credito', value: 'Credito' },
  { label: 'Boleta anterior', value: 'Boleta anterior' },
]

const facturaEstadoOptions = [
  { label: 'Sin gestion', value: 'SIN_GESTION' },
  { label: 'Pendiente', value: 'PENDIENTE' },
  { label: 'Facturado', value: 'FACTURADO' },
]

const ventasFiltradas = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) return ventas.value
  return ventas.value.filter((v) => {
    const productos = (v.productos || []).map(x => `${x.nombre} ${x.codigo}`).join(' ').toLowerCase()
    const s = `${v.comanda || ''} ${v.vendedor || ''} ${v.cliente || ''} ${v.venta_id || ''} ${productos}`.toLowerCase()
    return s.includes(term)
  })
})

const totalEdit = computed(() => {
  return (editForm.value.productos || []).reduce((acc, d) => acc + (Number(d.cantidad || 0) * Number(d.precio || 0)), 0)
})

function tipoColor(tipo) {
  const t = String(tipo || 'NORMAL').toUpperCase()
  if (t === 'POLLO') return 'orange'
  if (t === 'RES') return 'red'
  if (t === 'CERDO') return 'brown'
  return 'primary'
}

function estadoColor(estado) {
  if (estado === 'Activo') return 'green'
  if (estado === 'Anulada') return 'negative'
  return 'primary'
}

async function cargarVentas() {
  loading.value = true
  try {
    const res = await proxy.$axios.get('/digitador-factura/pedidos', {
      params: {
        fecha_inicio: fechaInicio.value,
        fecha_fin: fechaFin.value,
        solo_factura: soloFactura.value,
      },
    })
    ventas.value = Array.isArray(res.data?.data) ? res.data.data : []
    pedidosSinVenta.value = Array.isArray(res.data?.pedidos_sin_venta) ? res.data.pedidos_sin_venta : []
    stats.value = res.data?.stats || stats.value
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo cargar ventas de digitador factura')
  } finally {
    loading.value = false
  }
}

function abrirEdicion(venta) {
  editForm.value = {
    venta_id: venta.venta_id,
    tipo_pago: venta?.detalle_edit?.tipo_pago || venta.pago || 'Efectivo',
    facturado: !!(venta?.detalle_edit?.facturado ?? venta.facturado),
    factura_estado: venta?.detalle_edit?.factura_estado || venta.factura_estado || 'SIN_GESTION',
    productos: (venta?.detalle_edit?.productos || venta.productos || []).map((p) => ({
      id: p.id,
      codigo: p.codigo,
      nombre: p.nombre,
      tipo: p.tipo,
      cantidad: Number(p.cantidad || 0),
      precio: Number(p.precio || 0),
    })),
  }
  dialogEdit.value = true
}

async function guardarEdicion() {
  if (!editForm.value.venta_id) return
  try {
    saving.value = true
    await proxy.$axios.put(`/digitador-factura/ventas/${editForm.value.venta_id}`, {
      tipo_pago: editForm.value.tipo_pago,
      facturado: !!editForm.value.facturado,
      factura_estado: editForm.value.factura_estado,
      productos: (editForm.value.productos || []).map((d) => ({
        id: d.id,
        cantidad: Number(d.cantidad || 0),
        precio: Number(d.precio || 0),
      })),
    })
    proxy.$alert.success('Venta actualizada')
    dialogEdit.value = false
    await cargarVentas()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo actualizar venta')
  } finally {
    saving.value = false
  }
}

async function generarFacturaTodos() {
  loadingFacturar.value = true
  try {
    const res = await proxy.$axios.post('/digitador-factura/generar-factura-todos', {
      fecha_inicio: fechaInicio.value,
      fecha_fin: fechaFin.value,
    })
    proxy.$alert.success(`${res.data?.message || 'Proceso ejecutado'}: ${res.data?.ventas_marcadas || 0}`)
    await cargarVentas()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo marcar facturacion masiva')
  } finally {
    loadingFacturar.value = false
  }
}

onMounted(cargarVentas)
</script>

<style scoped>
.misped-page {
  background: linear-gradient(180deg, #eef4ff 0%, #f8fbff 30%, #ffffff 100%);
}
.hero-card {
  border: 1px solid #dbe7ff;
  background:
    radial-gradient(1200px 280px at 10% 0%, rgba(30, 136, 229, 0.12), transparent 70%),
    radial-gradient(1000px 240px at 95% 20%, rgba(102, 187, 106, 0.12), transparent 70%),
    #fff;
}
</style>

