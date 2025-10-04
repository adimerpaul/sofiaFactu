<template>
  <q-page class="q-pa-md">
    <q-table
      :rows="users" :columns="columns" dense wrap-cells flat bordered :rows-per-page-options="[0]"
      title="Usuarios" :filter="filter"
    >
      <template #top-right>
        <q-btn color="positive" label="Nuevo" @click="userNew" no-caps icon="add_circle_outline" :loading="loading" class="q-mr-sm"/>
        <q-btn color="primary" label="Actualizar" @click="usersGet" no-caps icon="refresh" :loading="loading" class="q-mr-sm"/>
        <q-input v-model="filter" label="Buscar" dense outlined>
          <template #append><q-icon name="search"/></template>
        </q-input>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
            <q-list>
              <q-item clickable @click="userEdit(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="edit"/></q-item-section>
                <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
              </q-item>
              <q-item clickable @click="userDelete(props.row.id)" v-close-popup>
                <q-item-section avatar><q-icon name="delete"/></q-item-section>
                <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
              </q-item>
              <q-item clickable @click="userEditPassword(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="lock_reset"/></q-item-section>
                <q-item-section><q-item-label>Cambiar contraseña</q-item-label></q-item-section>
              </q-item>
              <q-item clickable @click="cambiarAvatar(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="image"/></q-item-section>
                <q-item-section><q-item-label>Cambiar avatar</q-item-label></q-item-section>
              </q-item>
              <q-item clickable @click="permisosShow(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="lock" /></q-item-section>
                <q-item-section><q-item-label>Permisos</q-item-label></q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>

      <template #body-cell-role="props">
        <q-td :props="props">
          <q-chip :label="props.row.role" :color="$filters.color(props.row.role)" text-color="white" dense size="14px"/>
        </q-td>
      </template>

      <template #body-cell-avatar="props">
        <q-td :props="props">
          <q-avatar rounded>
            <q-img :src="`${$url}../../images/${props.row.avatar}`" width="40" height="40" v-if="props.row.avatar"/>
            <q-icon name="person" size="40px" v-else/>
          </q-avatar>
        </q-td>
      </template>

      <template #body-cell-permissions="props">
        <q-td :props="props">
          <div class="row items-center q-col-gutter-xs">
            <q-chip
              v-for="(perm, idx) in (props.row.permissions || []).slice(0, 3)"
              :key="perm.id" dense color="grey-3" text-color="black" size="12px" class="q-mr-xs q-mb-xs"
            >
              {{ perm.name }}
            </q-chip>

            <template v-if="(props.row.permissions || []).length > 3">
              <q-badge outline color="primary" class="q-ml-xs">
                +{{ (props.row.permissions || []).length - 3 }}
                <q-tooltip anchor="top middle" self="bottom middle" :offset="[0,8]">
                  <div class="text-left">
                    <div v-for="perm in props.row.permissions" :key="perm.id">• {{ perm.name }}</div>
                  </div>
                </q-tooltip>
              </q-badge>
            </template>

            <q-badge v-if="!(props.row.permissions || []).length" color="grey-5" text-color="white" outline>
              Sin permisos
            </q-badge>
          </div>
        </q-td>
      </template>
    </q-table>

    <!-- Dialogo: Crear/Editar -->
    <q-dialog v-model="userDialog" persistent>
      <q-card style="width: 400px">
        <q-card-section class="q-pb-none row items-center">
          <div>{{ actionUser }} usuario</div>
          <q-space/><q-btn icon="close" flat round dense @click="userDialog = false"/>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-form @submit="user.id ? userPut() : userPost()">
            <q-input v-model="user.name" label="Nombre" dense outlined :rules="[v => !!v || 'Campo requerido']"/>
            <q-input v-model="user.username" label="Usuario" dense outlined :rules="[v => !!v || 'Campo requerido']"/>
            <q-input v-model="user.password" label="Contraseña" dense outlined :rules="[v => !!v || 'Campo requerido']" v-if="!user.id"/>
            <q-select v-model="user.role" label="Rol" dense outlined :options="roles" :rules="[v => !!v || 'Campo requerido']"/>
            <q-input v-model="user.email" label="Email" dense outlined hint=""/>
            <q-input v-model="user.celular" label="Celular" dense outlined hint=""/>
<!--            <q-select v-model="user.area" label="Área" dense outlined :options="$areas" :rules="[v => !!v || 'Campo requerido']"/>-->
<!--            <q-select v-model="user.zona" label="Zona" dense outlined :options="$zonas" :rules="[v => !!v || 'Campo requerido']"/>-->
            <div class="text-right">
              <q-btn color="negative" label="Cancelar" @click="userDialog = false" no-caps :loading="loading"/>
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="loading" class="q-ml-sm"/>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialogo: Cambiar avatar -->
    <q-dialog v-model="cambioAvatarDialogo" persistent>
      <q-card>
        <q-card-section class="q-pb-none row items-center text-bold">
          Cambiar avatar
          <q-space/><q-btn icon="close" flat round dense @click="cambioAvatarDialogo = false"/>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-form @submit="userPut()">
            <div>
              <div style="position: relative;">
                <q-btn icon="edit" size="10px" class="absolute q-mt-sm q-ml-sm" @click="$refs.fileInput.click()" dense outline label="Cambiar foto" no-caps/>
              </div>
              <img :src="`${$url}../../images/${user.avatar}`" width="300" height="300" v-if="user.avatar"/>
              <q-icon name="person" size="100px" v-else/>
              <input ref="fileInput" type="file" style="display: none;" @change="onFileChange" accept="image/*"/>
            </div>
            <div class="text-right">
              <q-btn color="negative" label="Cancelar" @click="cambioAvatarDialogo = false" no-caps :loading="loading"/>
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="loading" class="q-ml-sm"/>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialogo: Permisos (lista plana en español) -->
    <q-dialog v-model="dialogPermisos" persistent>
      <q-card style="min-width: 460px">
        <q-card-section class="q-pb-none row items-center text-bold">
          Permisos de {{ user.username }}
          <q-space /><q-btn icon="close" flat round dense @click="dialogPermisos = false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-md items-center q-mb-sm">
            <div class="col">
              <q-input v-model="permFilter" dense outlined placeholder="Filtrar permisos... (ej. KPIs, Usuarios)">
                <template #append><q-icon name="search" /></template>
              </q-input>
            </div>
            <div class="col-auto">
              <q-btn dense flat icon="checklist_rtl" label="Marcar todo" @click="markAll(true)" />
              <q-btn dense flat icon="close" label="Quitar todo" @click="markAll(false)" />
            </div>
          </div>

          <q-list dense bordered>
            <q-item v-for="p in filteredPermissions" :key="p.id">
              <q-item-section>{{ p.name }}</q-item-section>
              <q-item-section side><q-toggle v-model="p.checked" /></q-item-section>
            </q-item>
          </q-list>
          <!--          <pre>{{permissions}}</pre>-->
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="negative" label="Cancelar" @click="dialogPermisos = false" no-caps :loading="loading" />
          <q-btn color="primary" label="Guardar" @click="permisosPost" no-caps :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'UsuariosPage',
  data() {
    return {
      users: [],
      user: {},
      userDialog: false,
      loading: false,
      actionUser: '',
      filter: '',
      roles: ['Admin', 'Usuario'],
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'name', label: 'Nombre', align: 'left', field: 'name' },
        { name: 'username', label: 'Usuario', align: 'left', field: 'username' },
        { name: 'avatar', label: 'Avatar', align: 'left', field: (row) => row.avatar },
        { name: 'role', label: 'Rol', align: 'left', field: 'role' },
        // { name: 'area', label: 'Área', align: 'left', field: 'area' },
        // { name: 'zona', label: 'Zona', align: 'left', field: 'zona' },
        { name: 'email', label: 'Email', align: 'left', field: 'email' },
        { name: 'celular', label: 'Celular', align: 'left', field: 'celular' },
        {
          name: 'permissions', label: 'Permisos', align: 'left',
          field: row => (row.permissions || []).map(p => p.name).join(', ')
        },
      ],
      // permisos
      permissions: [],      // [{id,name,checked}]
      dialogPermisos: false,
      permFilter: '',
      cambioAvatarDialogo: false,
    }
  },
  async mounted() { this.usersGet() },
  methods: {
    async usersGet() {
      this.loading = true
      try {
        const res = await this.$axios.get('users')
        this.users = res.data
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error cargando usuarios')
      } finally { this.loading = false }
    },
    userNew() {
      this.user = { name: '', username: '', password: '', role: 'Asistente', avatar: 'default.png', area: 'DNA', zona: 'CENTRAL', email: '', celular: '' }
      this.actionUser = 'Nuevo'
      this.userDialog = true
    },
    userEdit(user) {
      this.user = { ...user }
      this.actionUser = 'Editar'
      this.userDialog = true
    },
    userDelete(id) {
      this.$alert.dialog('¿Desea eliminar el usuario?')
        .onOk(async () => {
          this.loading = true
          try { await this.$axios.delete('users/' + id); this.$alert.success('Usuario eliminado'); this.usersGet() }
          catch (e) { this.$alert.error(e.response?.data?.message || 'No se pudo eliminar') }
          finally { this.loading = false }
        })
    },
    userPost() {
      this.loading = true
      this.$axios.post('users', this.user)
        .then(() => { this.userDialog = false; this.$alert.success('Usuario creado'); this.usersGet() })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo crear'))
        .finally(() => { this.loading = false })
    },
    userPut() {
      this.loading = true
      this.$axios.put('users/' + this.user.id, this.user)
        .then(() => { this.userDialog = false; this.$alert.success('Usuario actualizado'); this.usersGet() })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo actualizar'))
        .finally(() => { this.loading = false })
    },

    // Avatar
    onFileChange(e) {
      const file = e.target.files[0]
      const fd = new FormData()
      fd.append('avatar', file)
      this.loading = true
      this.$axios.post(`${this.user.id}/avatar`, fd, { headers: { 'Content-Type': 'multipart/form-data' }})
        .then(() => { this.cambioAvatarDialogo = false; this.$alert.success('Avatar actualizado'); this.usersGet() })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo actualizar'))
        .finally(() => { this.loading = false })
    },
    cambiarAvatar(user) { this.cambioAvatarDialogo = true; this.user = { ...user } },

    // Permisos
    async permisosShow(user) {
      this.user = { ...user }
      this.dialogPermisos = true
      this.loading = true
      try {
        const all = await this.$axios.get('permissions').then(r => r.data)              // [{id,name}]
        const userPermIds = await this.$axios.get(`users/${user.id}/permissions`).then(r => r.data) // [ids]
        const  valid = new Set([
          'Usuarios',
          'Impuestos',
          'Productos',
          'Ventas',
          'Nueva Venta',
          'Proveedores',
          'Compras',
          'Nueva Compra',
          'Clientes',
          'Productos por vencer',
          'Productos vencidos',
          'Pedidos',
          'Nuevo Pedido'
        ])
        this.permissions = all
          .filter(p => valid.has(p.name))
          .map(p => ({ ...p, checked: userPermIds.includes(p.id) }))
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error cargando permisos')
      } finally { this.loading = false }
    },
    async permisosPost() {
      this.loading = true
      try {
        const ids = this.permissions.filter(p => p.checked).map(p => p.id)
        await this.$axios.put(`users/${this.user.id}/permissions`, { permissions: ids })
        this.dialogPermisos = false
        this.$alert.success('Permisos actualizados')
        this.usersGet()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar')
      } finally { this.loading = false }
    },
    markAll(value) {
      this.permissions = this.permissions.map(p => ({ ...p, checked: !!value }))
    },

    userEditPassword(user) {
      this.user = { ...user }
      this.$alert.dialogPrompt('Nueva contraseña', 'Ingrese la nueva contraseña', 'password')
        .onOk(pass => {
          this.$axios.put('updatePassword/' + user.id, { password: pass })
            .then(() => { this.$alert.success('Contraseña actualizada de ' + user.username); this.usersGet() })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo actualizar'))
        })
    },
  },
  computed: {
    filteredPermissions() {
      if (!this.permFilter) return this.permissions
      const t = this.permFilter.toLowerCase()
      return this.permissions.filter(p => p.name.toLowerCase().includes(t))
    },
  },
}
</script>
