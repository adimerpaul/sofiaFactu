<template>
  <q-page class="q-pa-sm">
    <q-card flat bordered>
      <q-card-section class="row q-col-gutter-sm items-end">
        <div class="col-12 col-md-4">
          <q-input v-model="filter" dense outlined label="Buscar cliente" debounce="300" @update:model-value="clientesGet">
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-md-auto">
          <q-btn color="primary" icon="refresh" no-caps label="Actualizar" :loading="loading" @click="clientesGet" />
        </div>
        <div class="col-12 col-md-auto">
          <q-btn color="green" icon="add" no-caps label="Nuevo cliente" :loading="loading" @click="nuevoCliente" />
        </div>
      </q-card-section>

      <q-card-section>
        <q-markup-table dense flat bordered wrap-cells>
          <thead>
            <tr>
              <th>Opciones</th>
              <th>ID</th>
              <th>Nombre</th>
              <th>CI</th>
              <th>Telefono</th>
              <th>Zona</th>
              <th>Territorio</th>
              <th>Venta</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in clientes" :key="c.id">
              <td>
                <q-btn-dropdown label="Opciones" dense size="10px" color="primary" no-caps>
                  <q-item clickable v-close-popup @click="editarCliente(c)">
                    <q-item-section avatar><q-icon name="edit" /></q-item-section>
                    <q-item-section>Editar</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="eliminarCliente(c)">
                    <q-item-section avatar><q-icon name="delete" /></q-item-section>
                    <q-item-section>Eliminar</q-item-section>
                  </q-item>
                </q-btn-dropdown>
              </td>
              <td>{{ c.id }}</td>
              <td>{{ c.nombre }}</td>
              <td>{{ c.ci }}</td>
              <td>{{ c.telefono }}</td>
              <td>{{ c.zona || '-' }}</td>
              <td>{{ c.territorio || '-' }}</td>
              <td>{{ c.venta_estado || 'ACTIVO' }}</td>
            </tr>
          </tbody>
        </q-markup-table>
      </q-card-section>
    </q-card>

    <q-dialog v-model="dialog" maximized>
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ cliente.id ? 'Editar' : 'Nuevo' }} cliente</div>
          <q-space />
          <q-btn flat round dense icon="close" @click="dialog = false" />
        </q-card-section>

        <q-card-section>
          <q-form @submit="guardarCliente">
            <q-tabs v-model="tab" dense active-color="primary" align="left" class="text-grey-8">
              <q-tab name="basico" label="Basico" />
              <q-tab name="comercial" label="Comercial" />
              <q-tab name="visita" label="Visita" />
              <q-tab name="ubicacion" label="Ubicacion" />
              <q-tab name="fotos" label="Fotos" />
            </q-tabs>
            <q-separator class="q-my-sm" />

            <q-tab-panels v-model="tab" animated>
              <q-tab-panel name="basico">
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-md-4"><q-input v-model="cliente.nombre" label="Nombre" dense outlined :rules="[v => !!v || 'Requerido']" /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.ci" label="CI" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.complemento" label="Complemento" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.codigoTipoDocumentoIdentidad" label="Tipo doc" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.id_externo" label="ID externo" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.telefono" label="Telefono" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.email" label="Email" dense outlined type="email" /></div>
                  <div class="col-12 col-md-6"><q-input v-model="cliente.direccion" label="Direccion" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.empresa" label="Empresa" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.profecion" label="Profesion" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.sexo" label="Sexo" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.edad" label="Edad" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.est_civ" label="Estado civil" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.cod_ciudad" label="Cod ciudad" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.cod_nacio" label="Cod nacio" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model.number="cliente.tipodocu" label="Tipo documento" dense outlined type="number" /></div>
                </div>
              </q-tab-panel>

              <q-tab-panel name="comercial">
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-md-2"><q-input v-model.number="cliente.cod_car" label="Cod car" dense outlined type="number" /></div>
                  <div class="col-12 col-md-2"><q-input v-model.number="cliente.categoria" label="Categoria" dense outlined type="number" /></div>
                  <div class="col-12 col-md-2"><q-input v-model.number="cliente.codcli" label="Cod cliente" dense outlined type="number" /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.clinew" label="Cli new" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model="cliente.ci_vend" label="CI vendedor" dense outlined /></div>
                  <div class="col-12 col-md-2"><q-input v-model.number="cliente.imp_pieza" label="Imp pieza" dense outlined type="number" step="0.01" /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.supra_canal" label="Supra canal" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.canal" label="Canal" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.subcanal" label="Subcanal" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.zona" label="Zona" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.territorio" label="Territorio" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.transporte" label="Transporte" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.venta_estado" label="Estado venta" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.complto" label="Completo" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.tarjeta" label="Tarjeta" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.tipo_paciente" label="Tipo paciente" dense outlined /></div>
                </div>
              </q-tab-panel>

              <q-tab-panel name="visita">
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-md-3"><q-input v-model="cliente.correcli" label="Correo alterno" dense outlined /></div>
                  <div class="col-12 col-md-3"><q-input v-model.number="cliente.ctas_mont" label="Monto ctas" dense outlined type="number" step="0.01" /></div>
                  <div class="col-12 col-md-3"><q-input v-model.number="cliente.ctas_dias" label="Dias ctas" dense outlined type="number" /></div>
                  <div class="col-12 col-md-3"><q-input v-model="cliente.motivo_list_black" label="Motivo lista negra" dense outlined /></div>

                  <div class="col-12 q-mt-sm text-subtitle2">Dias de visita</div>
                  <div class="col-12 row q-col-gutter-sm">
                    <div class="col-auto"><q-toggle v-model="cliente.lu" label="Lun" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.ma" label="Mar" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.mi" label="Mie" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.ju" label="Jue" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.vi" label="Vie" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.sa" label="Sab" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.do" label="Dom" /></div>
                  </div>

                  <div class="col-12 q-mt-sm text-subtitle2">Estados / Flags</div>
                  <div class="col-12 row q-col-gutter-sm">
                    <div class="col-auto"><q-toggle v-model="cliente.list_black" label="Lista negra" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.list_blanck" label="Lista blanca" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.canmayni" label="Can mayni" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.baja" label="Baja" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.waths" label="WhatsApp" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.ctas_activo" label="Ctas activo" /></div>
                    <div class="col-auto"><q-toggle v-model="cliente.noesempre" label="No es empresa" /></div>
                  </div>
                </div>
              </q-tab-panel>

              <q-tab-panel name="ubicacion">
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-md-3"><q-input v-model.number="cliente.latitud" label="Latitud" dense outlined type="number" step="0.0000001" /></div>
                  <div class="col-12 col-md-3"><q-input v-model.number="cliente.longitud" label="Longitud" dense outlined type="number" step="0.0000001" /></div>
                  <div class="col-12 col-md-3"><q-btn color="primary" no-caps label="Centrar mapa" @click="centerMap" /></div>
                  <div class="col-12 col-md-3"><q-btn color="secondary" no-caps label="Posici�n actual" @click="useCurrentLocation" /></div>
                  <div class="col-12">
                    <div ref="mapRef" style="height: 380px; border-radius: 8px; border: 1px solid #ddd;" />
                    <div class="text-caption q-mt-xs">Haz click en el mapa para definir latitud y longitud.</div>
                  </div>
                </div>
              </q-tab-panel>

              <q-tab-panel name="fotos">
                <div class="row q-col-gutter-sm">
                  <div class="col-12">
                    <q-btn color="primary" no-caps icon="photo_camera" label="Agregar fotos (m�x. 3)" @click="$refs.fotosInput.click()" />
                    <input ref="fotosInput" type="file" accept="image/*" multiple style="display:none" @change="onFotosChange" />
                  </div>
                  <div class="col-12 text-caption">Puedes cargar hasta 3 fotos del lugar.</div>
                  <div class="col-12 row q-col-gutter-sm">
                    <div class="col-6 col-md-3" v-for="(f, idx) in previewFotos" :key="idx">
                      <q-card flat bordered>
                        <q-img :src="fotoUrl(f)" style="height: 130px" fit="cover" />
                        <q-card-actions align="right">
                          <q-btn flat dense color="negative" icon="delete" @click="removeFoto(idx)" />
                        </q-card-actions>
                      </q-card>
                    </div>
                  </div>
                </div>
              </q-tab-panel>
            </q-tab-panels>

            <div class="text-right q-mt-md">
              <q-btn flat no-caps label="Cancelar" color="grey-8" @click="dialog = false" :loading="loading" />
              <q-btn color="primary" no-caps label="Guardar" type="submit" class="q-ml-sm" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const emptyCliente = () => ({
  nombre: '', ci: '', telefono: '', direccion: '', complemento: '', codigoTipoDocumentoIdentidad: '', email: '',
  id_externo: '', cod_ciudad: '', cod_nacio: '', cod_car: null, est_civ: '', edad: '', empresa: '', categoria: null,
  imp_pieza: null, ci_vend: '', list_blanck: false, motivo_list_black: '', list_black: false, tipo_paciente: '', supra_canal: '',
  canal: '', subcanal: '', zona: '', latitud: null, longitud: null, transporte: '', territorio: '', codcli: null, clinew: '',
  venta_estado: 'ACTIVO', complto: '', tipodocu: null, lu: false, ma: false, mi: false, ju: false, vi: false, sa: false, do: false,
  correcli: '', canmayni: false, baja: false, profecion: '', waths: false, ctas_activo: false, ctas_mont: null, ctas_dias: null,
  sexo: '', noesempre: false, tarjeta: '', fotos: []
})

export default {
  name: 'ClientesPage',
  data () {
    return {
      loading: false,
      filter: '',
      clientes: [],
      dialog: false,
      tab: 'basico',
      cliente: emptyCliente(),
      previewFotos: [],
      map: null,
      marker: null,
      mapReady: false,
      pagination: {
        page: 1,
        rowsPerPage: 50,
      }
    }
  },
  mounted () {
    this.clientesGet()
  },
  methods: {
    fotoUrl (pathOrBlob) {
      if (!pathOrBlob) return ''
      if (pathOrBlob.startsWith('blob:')) return pathOrBlob
      return `${this.$url}../${pathOrBlob}`
    },
    async clientesGet () {
      this.loading = true
      try {
        const res = await this.$axios.get('clientes', {
          params: {
            search: this.filter,
            page: this.pagination.page,
            per_page: this.pagination.rowsPerPage,
          }
        })
        this.clientes = res.data.data || []
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar clientes')
      } finally {
        this.loading = false
      }
    },
    nuevoCliente () {
      this.cliente = emptyCliente()
      this.previewFotos = []
      this.tab = 'basico'
      this.dialog = true
      this.$nextTick(() => this.initMap())
    },
    editarCliente (cliente) {
      this.cliente = { ...emptyCliente(), ...cliente }
      this.previewFotos = [...(cliente.fotos || [])]
      this.tab = 'basico'
      this.dialog = true
      this.$nextTick(() => this.initMap())
    },
    eliminarCliente (cliente) {
      this.$alert.dialog('Desea eliminar este cliente?').onOk(async () => {
        this.loading = true
        try {
          await this.$axios.delete(`clientes/${cliente.id}`)
          this.$alert.success('Cliente eliminado')
          this.clientesGet()
        } catch (e) {
          this.$alert.error(e.response?.data?.message || 'No se pudo eliminar')
        } finally {
          this.loading = false
        }
      })
    },
    initMap () {
      if (!this.$refs.mapRef) return
      const lat = Number(this.cliente.latitud || -17.967)
      const lng = Number(this.cliente.longitud || -67.106)

      if (!this.map) {
        this.map = L.map(this.$refs.mapRef).setView([lat, lng], 13)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(this.map)

        this.map.on('click', (e) => {
          this.cliente.latitud = Number(e.latlng.lat.toFixed(7))
          this.cliente.longitud = Number(e.latlng.lng.toFixed(7))
          this.drawMarker()
        })
      }

      this.map.invalidateSize()
      this.map.setView([lat, lng], 13)
      this.drawMarker()
      this.mapReady = true
    },
    drawMarker () {
      if (!this.map || !this.cliente.latitud || !this.cliente.longitud) return
      const latlng = [Number(this.cliente.latitud), Number(this.cliente.longitud)]

      if (this.marker) {
        this.marker.setLatLng(latlng)
      } else {
        this.marker = L.circleMarker(latlng, {
          radius: 8,
          color: '#1976d2',
          fillColor: '#1976d2',
          fillOpacity: 0.8
        }).addTo(this.map)
      }
    },
    centerMap () {
      if (!this.map || !this.cliente.latitud || !this.cliente.longitud) return
      this.map.setView([Number(this.cliente.latitud), Number(this.cliente.longitud)], 15)
      this.drawMarker()
    },
    useCurrentLocation () {
      if (!navigator.geolocation) {
        this.$alert.error('Geolocalizaci�n no disponible')
        return
      }
      navigator.geolocation.getCurrentPosition((pos) => {
        this.cliente.latitud = Number(pos.coords.latitude.toFixed(7))
        this.cliente.longitud = Number(pos.coords.longitude.toFixed(7))
        this.centerMap()
      }, () => this.$alert.error('No se pudo obtener ubicaci�n'))
    },
    onFotosChange (e) {
      const files = Array.from(e.target.files || [])
      const existingCount = this.previewFotos.length
      const available = Math.max(0, 3 - existingCount)
      const selected = files.slice(0, available)

      selected.forEach(f => {
        f.__preview = URL.createObjectURL(f)
        this.previewFotos.push(f.__preview)
      })

      const current = this.cliente.fotos_files || []
      this.cliente.fotos_files = [...current, ...selected]
    },
    removeFoto (index) {
      const current = this.previewFotos[index]
      if (typeof current === 'string' && current.startsWith('blob:')) {
        URL.revokeObjectURL(current)
        const files = this.cliente.fotos_files || []
        const i = this.previewFotos.slice(0, index).filter(x => x.startsWith('blob:')).length
        files.splice(i, 1)
        this.cliente.fotos_files = files
      } else {
        const remove = this.cliente.remove_fotos || []
        remove.push(current)
        this.cliente.remove_fotos = remove
      }
      this.previewFotos.splice(index, 1)
    },
    async guardarCliente () {
      this.loading = true
      try {
        const fd = new FormData()
        const c = this.cliente

        const fields = [
          'nombre', 'ci', 'telefono', 'direccion', 'complemento', 'codigoTipoDocumentoIdentidad', 'email',
          'id_externo', 'cod_ciudad', 'cod_nacio', 'cod_car', 'est_civ', 'edad', 'empresa', 'categoria',
          'imp_pieza', 'ci_vend', 'list_blanck', 'motivo_list_black', 'list_black', 'tipo_paciente',
          'supra_canal', 'canal', 'subcanal', 'zona', 'latitud', 'longitud', 'transporte', 'territorio',
          'codcli', 'clinew', 'venta_estado', 'complto', 'tipodocu', 'lu', 'ma', 'mi', 'ju', 'vi', 'sa', 'do',
          'correcli', 'canmayni', 'baja', 'profecion', 'waths', 'ctas_activo', 'ctas_mont', 'ctas_dias',
          'sexo', 'noesempre', 'tarjeta'
        ]

        fields.forEach(k => {
          if (c[k] !== undefined && c[k] !== null) {
            if (typeof c[k] === 'boolean') fd.append(k, c[k] ? '1' : '0')
            else fd.append(k, c[k])
          }
        })

        ;(c.remove_fotos || []).forEach((f, i) => fd.append(`remove_fotos[${i}]`, f))
        ;(c.fotos_files || []).forEach((f, i) => fd.append(`fotos[${i}]`, f))

        if (c.id) {
          fd.append('_method', 'PUT')
          await this.$axios.post(`clientes/${c.id}`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
          this.$alert.success('Cliente actualizado')
        } else {
          await this.$axios.post('clientes', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
          this.$alert.success('Cliente creado')
        }

        this.dialog = false
        this.clientesGet()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar cliente')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
