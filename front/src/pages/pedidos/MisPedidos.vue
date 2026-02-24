<template>
  <q-page class="q-pa-sm misped-page">
    <q-card flat bordered class="hero-card q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md-4">
          <div class="text-subtitle2 text-grey-7">Mis pedidos por dia</div>
          <div class="text-h6 text-weight-bold">Gestion y envio</div>
        </div>
        <div class="col-12 col-md-3">
          <q-input v-model="fecha" type="date" dense outlined label="Fecha" />
        </div>
        <div class="col-12 col-md-2">
          <q-btn color="primary" icon="search" no-caps class="full-width" label="Consulta" :loading="loading" @click="cargarPedidos" />
        </div>
        <div class="col-12 col-md-3">
          <q-btn color="warning" icon="send" no-caps class="full-width text-black" label="Enviar todos los pedidos" :disable="enviables.length === 0" :loading="sendingAll" @click="enviarTodos" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="row q-col-gutter-sm">
        <div class="col-6 col-md-3">
          <q-chip square color="blue-8" text-color="white" class="full-width justify-center">Total: {{ stats.total }}</q-chip>
        </div>
        <div class="col-6 col-md-3">
          <q-chip square color="orange-8" text-color="white" class="full-width justify-center">Creado: {{ stats.creado }}</q-chip>
        </div>
        <div class="col-6 col-md-3">
          <q-chip square color="deep-orange-7" text-color="white" class="full-width justify-center">Pendiente: {{ stats.pendiente }}</q-chip>
        </div>
        <div class="col-6 col-md-3">
          <q-chip square color="green-8" text-color="white" class="full-width justify-center">Enviado: {{ stats.enviado }}</q-chip>
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered>
      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-12 col-md-auto">
          <q-btn color="green-7" icon="download" no-caps label="Reporte Pollo" @click="exportarReporteTipo('POLLO')" />
        </div>
        <div class="col-12 col-md-auto">
          <q-btn color="purple-7" icon="download" no-caps label="Reporte Res" @click="exportarReporteTipo('RES')" />
        </div>
        <div class="col-12 col-md-auto">
          <q-btn color="cyan-7" icon="download" no-caps label="Reporte Cerdo" @click="exportarReporteTipo('CERDO')" />
        </div>
      </q-card-section>

      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-12 col-md-4">
          <q-input v-model="search" dense outlined label="Buscar cliente/comanda/producto" debounce="250">
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-md-auto">
          <q-chip color="indigo-7" text-color="white">Pedidos: {{ pedidosFiltrados.length }}</q-chip>
        </div>
      </q-card-section>

      <q-markup-table flat dense wrap-cells>
        <thead>
        <tr class="bg-grey-2">
          <th>Opciones</th>
          <th>Comanda</th>
          <th>Cliente</th>
          <th>Producto</th>
          <th>Fec/Hora</th>
          <th>Pago</th>
          <th>Factura</th>
          <th>Estado</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="pedido in pedidosFiltrados" :key="pedido.id">
          <td>
            <q-btn-dropdown v-if="isEditable(pedido)" color="primary" label="Opciones" dense no-caps size="10px">
              <q-item clickable v-close-popup @click="editarPedido(pedido)">
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="enviarUno(pedido)">
                <q-item-section avatar><q-icon name="send" /></q-item-section>
                <q-item-section>Mandar</q-item-section>
              </q-item>
            </q-btn-dropdown>
            <q-chip v-else dense color="green-7" text-color="white">Ya mandado</q-chip>
          </td>
          <td>{{ pedido.id }}</td>
          <td>{{ pedido.cliente?.nombre || '-' }}</td>
          <td>
<!--            <q-list dense separator>-->
<!--              <q-item v-for="d in (pedido.detalles || [])" :key="d.id" class="q-px-none">-->
<!--                <q-item-section>-->
<!--                  <q-item-label>{{ d.producto?.nombre || ('Producto ' + d.producto_id) }} x {{ d.cantidad }}</q-item-label>-->
<!--                </q-item-section>-->
<!--              </q-item>-->
<!--            </q-list>-->
            <ul style="padding: 0; margin: 0; list-style: none;">
              <li v-for="d in (pedido.detalles || [])" :key="d.id" style="font-size: 0.9em; border-bottom: 1px solid #eee; padding: 0;">
                {{ d.producto?.nombre || ('Producto ' + d.producto_id) }} x {{ d.cantidad }}
              </li>
            </ul>
          </td>
          <td>{{ pedido.fecha }} {{ pedido.hora || '' }}</td>
          <td>{{ pedido.tipo_pago || '-' }}</td>
          <td>{{ pedido.facturado ? 'SI' : 'NO' }}</td>
          <td>
            <q-chip dense :color="estadoColor(pedido.estado)" text-color="white">{{ pedido.estado }}</q-chip>
          </td>
        </tr>
        <tr v-if="pedidosFiltrados.length === 0">
          <td colspan="8" class="text-center text-grey-7">Sin datos disponibles</td>
        </tr>
        </tbody>
      </q-markup-table>
    </q-card>

    <q-dialog v-model="dialogEdit" maximized>
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Editar pedido #{{ editForm.id }}</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pt-sm">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-4">
              <q-option-group v-model="editForm.tipo_pago" :options="tiposPagoOptions" type="radio" color="primary" inline />
            </div>
            <div class="col-12 col-md-2"><q-toggle v-model="editForm.facturado" label="Facturado" /></div>
            <div class="col-12 col-md-3"><q-input v-model="editForm.fecha" type="date" dense outlined label="Fecha" /></div>
            <div class="col-12 col-md-3">
              <q-select v-model="editForm.hora" :options="horariosPedido" dense outlined emit-value map-options label="Horario" />
            </div>
            <div class="col-12 col-md-12"><q-input v-model="editForm.observaciones" dense outlined label="Comentario" /></div>
            <div class="col-12 col-md-10">
              <q-select
                v-model="newProductoId"
                :options="productosOptions"
                option-value="id"
                option-label="label"
                emit-value
                map-options
                dense
                outlined
                label="Productos"
                use-input
                input-debounce="350"
                @filter="filtrarProductos"
              >
                <template #selected-item="scope">
                  <div class="row items-center no-wrap q-gutter-xs">
                    <q-avatar rounded size="24px"><q-img :src="productImageUrl(scope?.opt?.imagen)" /></q-avatar>
                    <span class="ellipsis">{{ scope?.opt?.label || '' }}</span>
                  </div>
                </template>
                <template #option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section avatar>
                      <q-avatar rounded size="28px"><q-img :src="productImageUrl(scope.opt.imagen)" /></q-avatar>
                    </q-item-section>
                    <q-item-section><q-item-label>{{ scope.opt.label }}</q-item-label></q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>
            <div class="col-12 col-md-2">
              <q-btn color="negative" class="full-width" icon="add" @click="agregarProducto" />
            </div>
          </div>

          <q-markup-table dense flat bordered class="q-mt-sm">
            <thead>
            <tr>
              <th>Detalle</th>
              <th>Subtotal</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Cod</th>
              <th>Nombre</th>
              <th>Obs</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(d, i) in editForm.productos" :key="`${d.producto_id}-${i}`">
              <td>
                <q-btn-dropdown dense :label="'Op(' + d.tipo + ')'" :color="tipoColor(d.tipo)" no-caps size="10px">
                  <q-list>
                    <q-item clickable v-ripple v-close-popup @click="openDetalleDialog(d, i)">
                      <q-item-section avatar><q-icon name="tune" color="purple" /></q-item-section>
                      <q-item-section>Editar detalle</q-item-section>
                    </q-item>
                    <q-item clickable v-ripple v-close-popup @click="editForm.productos.splice(i, 1)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section>Eliminar producto</q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ (Number(d.cantidad) * Number(d.precio)).toFixed(2) }}</td>
              <td><input v-model.number="d.cantidad" type="text" style="width: 40px" @input="d.cantidad = d.cantidad < 1 ? 1 : d.cantidad" /></td>
              <td><input v-model.number="d.precio" type="text" style="width: 50px" @input="d.precio = d.precio < 0 ? 0 : d.precio" /></td>
              <td>{{ d.codigo || d.producto_id }}</td>
              <td>
                <div class="row items-center no-wrap q-gutter-sm">
                  <q-avatar rounded size="30px"><q-img :src="productImageUrl(d.imagen)" /></q-avatar>
                  <div>{{ d.nombre }}</div>
                </div>
              </td>
              <td>{{ d.observacion || '-' }}</td>
            </tr>
            <tr v-if="editForm.productos.length === 0">
              <td colspan="7" class="text-grey-7">Sin datos disponibles</td>
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

    <q-dialog v-model="dialogDetalle">
      <q-card style="width: 450px; max-width: 96vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Pedido {{ detalleTipoLabel }}</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-sm" v-if="detalleTipo === 'NORMAL'">
            <div class="col-12"><q-input v-model="detalleEdit.observacion" dense outlined label="Observacion detalle" /></div>
          </div>
          <div class="row q-col-gutter-sm" v-else-if="detalleTipo === 'RES'">
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.precio_res" dense outlined label="Precio RES" /></div>
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.res_trozado" dense outlined label="Res trozado" /></div>
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.res_entero" dense outlined label="Res entero" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.res_pierna" dense outlined label="Res pierna" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.res_brazo" dense outlined label="Res brazo" /></div>
            <div class="col-12"><q-input v-model="detalleEdit.observacion" dense outlined label="Observacion detalle" /></div>
          </div>
          <div class="row q-col-gutter-sm" v-else-if="detalleTipo === 'CERDO'">
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.cerdo_precio_total" dense outlined label="Precio total" /></div>
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.cerdo_entero" dense outlined label="Cerdo entero" /></div>
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.cerdo_kilo" dense outlined label="Cerdo kilo" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.cerdo_desmembrado" dense outlined label="Cerdo desmembrado" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.cerdo_corte" dense outlined label="Cerdo corte" /></div>
            <div class="col-12"><q-input v-model="detalleEdit.observacion" dense outlined label="Observacion detalle" /></div>
          </div>
          <div class="row q-col-gutter-sm" v-else>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_b5" dense outlined label="Cja b5" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_b5" dense outlined label="Uni b5" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_b6" dense outlined label="Cja b6" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_b6" dense outlined label="Uni b6" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_104" dense outlined label="Cja-104" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_104" dense outlined label="Unid-104" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_105" dense outlined label="Cja-105" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_105" dense outlined label="Unid-105" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_106" dense outlined label="Cja-106" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_106" dense outlined label="Unid-106" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_107" dense outlined label="Cja-107" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_107" dense outlined label="Unid-107" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_108" dense outlined label="Cja-108" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_108" dense outlined label="Unid-108" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_109" dense outlined label="Cja-109" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_109" dense outlined label="Unid-109" /></div>
            <div class="col-12"><q-input v-model="detalleEdit.pollo_rango_unidades" dense outlined label="Rango pollo (unidades)" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_ala" dense outlined label="Ala" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_ala_unidad" :options="unidadesPollo" dense outlined label="Unidad ala" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cadera" dense outlined label="Cadera" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_cadera_unidad" :options="unidadesPollo" dense outlined label="Unidad cadera" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_pecho" dense outlined label="Pecho" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_pecho_unidad" :options="unidadesPollo" dense outlined label="Unidad pecho" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_pi_mu" dense outlined label="Pi/Mu" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_pi_mu_unidad" :options="unidadesPollo" dense outlined label="Unidad pi/mu" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_filete" dense outlined label="Filete" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_filete_unidad" :options="unidadesPollo" dense outlined label="Unidad filete" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cuello" dense outlined label="Cuello" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_cuello_unidad" :options="unidadesPollo" dense outlined label="Unidad cuello" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_hueso" dense outlined label="Hueso" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_hueso_unidad" :options="unidadesPollo" dense outlined label="Unidad hueso" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_menudencia" dense outlined label="Menudencia" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_menudencia_unidad" :options="unidadesPollo" dense outlined label="Unidad menudencia" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_bs" dense outlined label="BS" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_bs2" dense outlined label="BS2" /></div>
            <div class="col-12"><q-input v-model="detalleEdit.observacion" dense outlined label="Observacion detalle" /></div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat color="negative" label="Cerrar" v-close-popup />
          <q-btn color="primary" label="Guardar detalle" @click="saveDetalleDialog" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'
import { Excel } from 'src/addons/Excel'

const { proxy } = getCurrentInstance()

const fecha = ref(new Date().toISOString().slice(0, 10))
const loading = ref(false)
const sendingAll = ref(false)
const saving = ref(false)
const reporte = ref('POLLO')
const search = ref('')

const pedidos = ref([])
const stats = ref({ total: 0, creado: 0, pendiente: 0, enviado: 0 })

const dialogEdit = ref(false)
const dialogDetalle = ref(false)
const detalleEditIndex = ref(-1)
const detalleEdit = ref({})
const detalleTipo = ref('NORMAL')
const unidadesPollo = ['KG', 'CAJA', 'UNIDAD']
const editForm = ref({ id: null, productos: [] })
const tiposPagoOptions = [
  { label: 'Contado', value: 'Contado' },
  { label: 'Pago QR', value: 'QR' },
  { label: 'Credito', value: 'Credito' },
  { label: 'Boleta anterior', value: 'Boleta anterior' }
]
const horariosPedido = [
  { label: '06:00-07:30', value: '06:00-07:30' },
  { label: '07:30-09:00', value: '07:30-09:00' },
  { label: '09:00-10:30', value: '09:00-10:30' },
  { label: '10:30-12:00', value: '10:30-12:00' },
  { label: 'SEGUNDA VUELTA', value: 'SEGUNDA VUELTA' },
  { label: 'SE RECOGE', value: 'SE RECOGE' },
]

const productosSource = ref([])
const productosOptions = ref([])
const newProductoId = ref(null)

const enviables = computed(() => pedidos.value.filter(p => isEditable(p)))
const totalEdit = computed(() => editForm.value.productos.reduce((acc, p) => acc + (Number(p.cantidad || 0) * Number(p.precio || 0)), 0))
const detalleTipoLabel = computed(() => detalleTipo.value === 'RES' ? 'Res' : detalleTipo.value === 'CERDO' ? 'Cerdo' : detalleTipo.value === 'POLLO' ? 'Pollo' : 'Normal')

const filasReporte = computed(() => {
  const tipo = reporte.value
  const out = []
  pedidos.value.forEach((p) => {
    ;(p.detalles || []).forEach((d) => {
      const tipoDetalle = normalizeTipo(d.producto?.tipo)
      if (tipoDetalle !== tipo) return
      out.push({
        key: `${p.id}-${d.id}`,
        pedido: p,
        detalle: d,
      })
    })
  })
  return out
})

const filasReporteFiltradas = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) return filasReporte.value
  return filasReporte.value.filter(r => {
    const cliente = (r.pedido.cliente?.nombre || '').toLowerCase()
    const codcli = String(r.pedido.cliente?.codcli || '')
    const comanda = String(r.pedido.id)
    return cliente.includes(term) || codcli.includes(term) || comanda.includes(term)
  })
})

const pedidosFiltrados = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) return pedidos.value
  return pedidos.value.filter((p) => {
    const cliente = (p.cliente?.nombre || '').toLowerCase()
    const comanda = String(p.id)
    const productos = (p.detalles || []).map(d => d.producto?.nombre || '').join(' ').toLowerCase()
    return cliente.includes(term) || comanda.includes(term) || productos.includes(term)
  })
})

function normalizeTipo (tipo) {
  const t = String(tipo || 'NORMAL').trim().toUpperCase()
  if (t === 'POLLO' || t === 'RES' || t === 'CERDO') return t
  return 'NORMAL'
}

function estadoColor (estado) {
  if (estado === 'Enviado') return 'green'
  if (estado === 'Pendiente') return 'deep-orange'
  if (estado === 'Creado') return 'orange'
  if (estado === 'Anulado') return 'negative'
  return 'primary'
}

function tipoColor (tipo) {
  if (tipo === 'POLLO') return 'orange'
  if (tipo === 'RES') return 'red'
  if (tipo === 'CERDO') return 'brown'
  return 'primary'
}

function isEditable (pedido) {
  return pedido && (pedido.estado === 'Creado' || pedido.estado === 'Pendiente')
}

function productImageUrl (path) {
  const safe = path || 'uploads/default.png'
  return `${proxy.$url}../${safe}`
}

function detalleDefaultsByTipo (tipo) {
  if (tipo === 'RES') {
    return { precio_res: '', res_trozado: '', res_entero: '', res_pierna: '', res_brazo: '', observacion: '' }
  }
  if (tipo === 'CERDO') {
    return { cerdo_precio_total: '', cerdo_entero: '', cerdo_desmembrado: '', cerdo_corte: '', cerdo_kilo: '', observacion: '' }
  }
  if (tipo === 'POLLO') {
    return {
      pollo_cja_b5: '', pollo_uni_b5: '', pollo_cja_b6: '', pollo_uni_b6: '',
      pollo_cja_104: '', pollo_uni_104: '', pollo_cja_105: '', pollo_uni_105: '',
      pollo_cja_106: '', pollo_uni_106: '', pollo_cja_107: '', pollo_uni_107: '',
      pollo_cja_108: '', pollo_uni_108: '', pollo_cja_109: '', pollo_uni_109: '',
      pollo_rango_unidades: '', pollo_ala: '', pollo_ala_unidad: 'KG', pollo_cadera: '',
      pollo_cadera_unidad: 'KG', pollo_pecho: '', pollo_pecho_unidad: 'KG', pollo_pi_mu: '',
      pollo_pi_mu_unidad: 'KG', pollo_filete: '', pollo_filete_unidad: 'KG', pollo_cuello: '',
      pollo_cuello_unidad: 'KG', pollo_hueso: '', pollo_hueso_unidad: 'KG',
      pollo_menudencia: '', pollo_menudencia_unidad: 'KG', pollo_bs: '', pollo_bs2: '',
      observacion: ''
    }
  }
  return { observacion: '' }
}

function openDetalleDialog (item, index) {
  detalleEditIndex.value = index
  detalleTipo.value = normalizeTipo(item?.tipo)
  detalleEdit.value = { ...detalleDefaultsByTipo(detalleTipo.value), ...(item?.detalle_extra || {}) }
  dialogDetalle.value = true
}

function saveDetalleDialog () {
  if (detalleEditIndex.value < 0 || !editForm.value.productos[detalleEditIndex.value]) return
  const current = editForm.value.productos[detalleEditIndex.value]
  current.detalle_extra = { ...detalleEdit.value }
  current.observacion = detalleEdit.value.observacion || current.observacion || ''
  dialogDetalle.value = false
  detalleEditIndex.value = -1
  detalleEdit.value = {}
}

function filtrarProductos (val, update) {
  update(() => {
    const needle = (val || '').toLowerCase()
    if (!needle) {
      productosOptions.value = [...productosSource.value]
      return
    }
    productosOptions.value = productosSource.value.filter(p => p.label.toLowerCase().includes(needle))
  })
}

function agregarProducto () {
  if (!newProductoId.value) return
  const p = productosSource.value.find(x => x.id === newProductoId.value)
  if (!p) return
  const ex = editForm.value.productos.find(x => x.producto_id === p.id)
  if (ex) {
    ex.cantidad += 1
  } else {
    editForm.value.productos.push({
      producto_id: p.id,
      codigo: p.codigo,
      nombre: p.nombre,
      imagen: p.imagen || 'uploads/default.png',
      tipo: p.tipo,
      cantidad: 1,
      precio: p.precio,
      observacion: '',
      detalle_extra: detalleDefaultsByTipo(p.tipo),
    })
  }
  newProductoId.value = null
}

function editarPedido (pedido) {
  if (!isEditable(pedido)) {
    proxy.$alert.error('El pedido enviado ya no se puede modificar')
    return
  }
  editForm.value = {
    id: pedido.id,
    fecha: pedido.fecha,
    hora: pedido.hora || horariosPedido[0].value,
    facturado: !!pedido.facturado,
    tipo_pago: pedido.tipo_pago || 'Contado',
    observaciones: pedido.observaciones || '',
    productos: (pedido.detalles || []).map(d => ({
      producto_id: d.producto_id,
      codigo: d.producto?.codigo,
      nombre: d.producto?.nombre || `Producto ${d.producto_id}`,
      imagen: d.producto?.imagen || 'uploads/default.png',
      tipo: normalizeTipo(d.producto?.tipo),
      cantidad: Number(d.cantidad || 0),
      precio: Number(d.precio || 0),
      observacion: d.observacion_detalle || '',
      detalle_extra: { ...detalleDefaultsByTipo(normalizeTipo(d.producto?.tipo)), ...(d.detalle_extra || {}) },
    }))
  }
  dialogEdit.value = true
}

async function guardarEdicion () {
  if (!editForm.value.id) return
  try {
    const productos = editForm.value.productos
      .map((p) => ({
        producto_id: p.producto_id,
        cantidad: Number(p.cantidad || 0),
        precio: Number(p.precio || 0),
        observacion: p.observacion || '',
        detalle_extra: p.detalle_extra || {}
      }))
      .filter((p) => p.cantidad > 0)

    if (productos.length === 0) {
      proxy.$alert.error('Debe agregar al menos un producto')
      return
    }

    saving.value = true
    await proxy.$axios.put(`/pedidos/${editForm.value.id}`, {
      fecha: editForm.value.fecha,
      hora: editForm.value.hora,
      facturado: editForm.value.facturado,
      tipo_pago: editForm.value.tipo_pago,
      observaciones: editForm.value.observaciones,
      comentario_visita: editForm.value.observaciones,
      productos,
    })
    proxy.$alert.success('Pedido actualizado')
    dialogEdit.value = false
    await cargarPedidos()
  } catch (e) {
    proxy.$alert.error(e?.message || e?.response?.data?.message || 'No se pudo guardar')
  } finally {
    saving.value = false
  }
}

async function enviarUno (pedido) {
  if (!isEditable(pedido)) return
  try {
    await proxy.$axios.put(`/pedidos/${pedido.id}/enviar`)
    proxy.$alert.success('Pedido enviado')
    await cargarPedidos()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo enviar')
  }
}

async function enviarTodos () {
  if (enviables.value.length === 0) return
  sendingAll.value = true
  try {
    await proxy.$axios.post('/pedidos/enviar-mis-pedidos', {
      fecha: fecha.value,
      ids: enviables.value.map(p => p.id)
    })
    proxy.$alert.success('Pedidos enviados')
    await cargarPedidos()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudieron enviar los pedidos')
  } finally {
    sendingAll.value = false
  }
}

function exportarReporteTipo (tipo) {
  reporte.value = tipo
  exportarReporte()
}

function exportarReporte () {
  const vendor = (proxy.$store?.user?.name || 'VENDEDOR').replace(/\s+/g, '_').toUpperCase()
  const tipo = reporte.value
  const list = filasReporteFiltradas.value

  if (tipo === 'POLLO') {
    const content = list.map(({ pedido, detalle }) => {
      const ex = detalle?.detalle_extra || {}
      return {
        cliente: pedido?.cliente?.nombre || '',
        b5_cja: ex.pollo_cja_b5 || '',
        b5_uni: ex.pollo_uni_b5 || '',
        b6_cja: ex.pollo_cja_b6 || '',
        b6_uni: ex.pollo_uni_b6 || '',
        c104_cja: ex.pollo_cja_104 || '',
        c104_uni: ex.pollo_uni_104 || '',
        c105_cja: ex.pollo_cja_105 || '',
        c105_uni: ex.pollo_uni_105 || '',
        c106_cja: ex.pollo_cja_106 || '',
        c106_uni: ex.pollo_uni_106 || '',
        c107_cja: ex.pollo_cja_107 || '',
        c107_uni: ex.pollo_uni_107 || '',
        c108_cja: ex.pollo_cja_108 || '',
        c108_uni: ex.pollo_uni_108 || '',
        c109_cja: ex.pollo_cja_109 || '',
        c109_uni: ex.pollo_uni_109 || '',
        rango: ex.pollo_rango_unidades || '',
        ala: ex.pollo_ala || '',
        cadera: ex.pollo_cadera || '',
        pecho: ex.pollo_pecho || '',
        pimu: ex.pollo_pi_mu || '',
        filete: ex.pollo_filete || '',
        cuello: ex.pollo_cuello || '',
        hueso: ex.pollo_hueso || '',
        menudencia: ex.pollo_menudencia || '',
        bs: ex.pollo_bs || '',
        bs2: ex.pollo_bs2 || '',
        fact: pedido.facturado ? 'SI' : 'NO',
        horario: pedido.hora || '',
        comentario: detalle?.observacion_detalle || pedido?.observaciones || '',
      }
    })
    Excel.export([{
      columns: [
        { label: 'CLIENTE', value: 'cliente' },
        { label: 'B5 cja', value: 'b5_cja' }, { label: 'B5 uni', value: 'b5_uni' },
        { label: 'B6 cja', value: 'b6_cja' }, { label: 'B6 uni', value: 'b6_uni' },
        { label: '104 cja', value: 'c104_cja' }, { label: '104 uni', value: 'c104_uni' },
        { label: '105 cja', value: 'c105_cja' }, { label: '105 uni', value: 'c105_uni' },
        { label: '106 cja', value: 'c106_cja' }, { label: '106 uni', value: 'c106_uni' },
        { label: '107 cja', value: 'c107_cja' }, { label: '107 uni', value: 'c107_uni' },
        { label: '108 cja', value: 'c108_cja' }, { label: '108 uni', value: 'c108_uni' },
        { label: '109 cja', value: 'c109_cja' }, { label: '109 uni', value: 'c109_uni' },
        { label: 'Rango', value: 'rango' }, { label: 'Ala', value: 'ala' },
        { label: 'Cadera', value: 'cadera' }, { label: 'Pecho', value: 'pecho' },
        { label: 'Pi/Mu', value: 'pimu' }, { label: 'Filete', value: 'filete' },
        { label: 'Cuello', value: 'cuello' }, { label: 'Hueso', value: 'hueso' },
        { label: 'Menudencia', value: 'menudencia' }, { label: 'Bs', value: 'bs' },
        { label: 'Bs.2', value: 'bs2' }, { label: 'Fact', value: 'fact' },
        { label: 'Horario', value: 'horario' }, { label: 'Comentario', value: 'comentario' },
      ],
      content
    }], `${vendor}_${fecha.value.replaceAll('-', '')}_poll`)
    return
  }

  if (tipo === 'RES') {
    const content = list.map(({ pedido, detalle }) => {
      const ex = detalle?.detalle_extra || {}
      return {
        cliente: pedido?.cliente?.nombre || '',
        precio: ex.precio_res || detalle?.precio || '',
        trozado: ex.res_trozado || '',
        ent_med: ex.res_entero || '',
        pierna: ex.res_pierna || '',
        brazo: ex.res_brazo || '',
        observacion: detalle?.observacion_detalle || '',
        cantidad: detalle?.cantidad || '',
        fact: pedido.facturado ? 'SI' : 'NO',
        horario: pedido.hora || '',
        comentario: pedido.observaciones || '',
      }
    })
    Excel.export([{
      columns: [
        { label: 'CLIENTE', value: 'cliente' },
        { label: 'Precio', value: 'precio' },
        { label: 'TROZADO', value: 'trozado' },
        { label: 'ENT/MED', value: 'ent_med' },
        { label: 'PIERNA', value: 'pierna' },
        { label: 'BRAZO', value: 'brazo' },
        { label: 'OBSERVACION', value: 'observacion' },
        { label: 'CNTAD', value: 'cantidad' },
        { label: 'FACT', value: 'fact' },
        { label: 'HORARIO', value: 'horario' },
        { label: 'COMENTARIO', value: 'comentario' },
      ],
      content
    }], `${vendor}_res_${fecha.value.replaceAll('-', '')}`)
    return
  }

  const content = list.map(({ pedido, detalle }) => {
    const ex = detalle?.detalle_extra || {}
    return {
      cliente: pedido?.cliente?.nombre || '',
      precio: ex.cerdo_precio_total || detalle?.precio || '',
      total: ex.cerdo_entero || '',
      entero: ex.cerdo_entero || '',
      membra: ex.cerdo_desmembrado || '',
      corte: ex.cerdo_corte || '',
      observacion: detalle?.observacion_detalle || '',
      cantidad: detalle?.cantidad || '',
      fact: pedido.facturado ? 'SI' : 'NO',
      horario: pedido.hora || '',
      comentario: pedido.observaciones || '',
    }
  })
  Excel.export([{
    columns: [
      { label: 'CLIENTE', value: 'cliente' },
      { label: 'Precio', value: 'precio' },
      { label: 'TOTAL', value: 'total' },
      { label: 'Entero', value: 'entero' },
      { label: 'membra', value: 'membra' },
      { label: 'Corte', value: 'corte' },
      { label: 'OBSERVACION', value: 'observacion' },
      { label: 'CNTAD', value: 'cantidad' },
      { label: 'FACT', value: 'fact' },
      { label: 'HORARIO', value: 'horario' },
      { label: 'COMENTARIO', value: 'comentario' },
    ],
    content
  }], `${vendor}_cerd_${fecha.value.replaceAll('-', '')}`)
}

async function cargarPedidos () {
  loading.value = true
  try {
    const res = await proxy.$axios.get('/mis-pedidos', { params: { fecha: fecha.value } })
    pedidos.value = res.data?.data || []
    stats.value = res.data?.stats || { total: 0, creado: 0, pendiente: 0, enviado: 0 }
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo cargar pedidos')
  } finally {
    loading.value = false
  }
}

async function cargarProductos () {
  try {
    const res = await proxy.$axios.get('/productosAll')
    const rows = Array.isArray(res.data) ? res.data : []
    productosSource.value = rows.map((p) => ({
      id: p.id,
      codigo: p.codigo,
      nombre: p.nombre,
      imagen: p.imagen || 'uploads/default.png',
      tipo: normalizeTipo(p.tipo),
      precio: Number(p.precio1 || 0),
      label: `${p.codigo || p.id}-${p.nombre} ${Number(p.precio1 || 0).toFixed(2)} Bs`,
    }))
    productosOptions.value = [...productosSource.value]
  } catch (_) {
    productosSource.value = []
    productosOptions.value = []
  }
}

onMounted(async () => {
  await Promise.all([cargarPedidos(), cargarProductos()])
})
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
