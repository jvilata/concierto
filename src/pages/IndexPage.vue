<template>
  <q-page class="scanner-page">
    <header class="topbar">
      <div class="brand-mark" aria-hidden="true"><q-icon name="volunteer_activism" /></div>
      <div class="brand-copy">
        <div class="eyebrow">CONTROL DE ACCESO</div>
        <div class="brand-title">Concierto <span>Solidario</span></div>
      </div>
      <div class="event-status"><span class="live-dot"></span> OPERATIVO</div>
      <q-btn
        flat
        round
        :icon="isAuthenticated ? 'verified_user' : 'login'"
        :color="isAuthenticated ? 'positive' : 'white'"
        :aria-label="isAuthenticated ? 'Cerrar sesión' : 'Iniciar sesión'"
        :title="isAuthenticated ? 'Cerrar sesión' : 'Iniciar sesión'"
        @click="handleAuthButton"
      />
    </header>

    <main class="workspace">
      <section class="scanner-column">
        <div class="section-heading">
          <div>
            <div class="eyebrow text-muted">PUESTO DE VALIDACIÓN</div>
            <h1>Escanear código</h1>
          </div>
          <div class="mode-switch">
            <q-btn-toggle
              v-model="mode"
              unelevated
              no-caps
              toggle-color="accent"
              :options="modeOptions"
              aria-label="Tipo de validación"
            />
          </div>
        </div>

        <div class="mode-note">
          <q-icon :name="mode === 'entrada' ? 'confirmation_number' : mode === 'consumicion' ? 'local_cafe' : 'block'" />
          <span v-if="mode === 'entrada'">Validación de acceso al recinto</span>
          <span v-else-if="mode === 'consumicion'">Canje de la consumición incluida</span>
          <span v-else>Gestión de anulación de entradas</span>
        </div>

        <q-form v-if="mode === 'anulacion'" class="annulation-form" @submit.prevent="annulEntry">
          <q-input
            v-model.trim="annulationForm.autorizacion"
            outlined
            dark
            label="Autorización"
            autocomplete="off"
            :disable="annulling"
            required
          />
          <q-select
            v-model="annulationForm.accion"
            :options="annulationActionOptions"
            outlined
            dark
            emit-value
            map-options
            label="Acción"
            :disable="annulling"
            required
          />
          <q-input
            v-model.trim="annulationForm.email"
            outlined
            dark
            type="email"
            label="Email"
            autocomplete="email"
            :disable="annulling"
          />
          <q-input
            v-model="annulationForm.clave"
            outlined
            dark
            type="password"
            label="Clave"
            autocomplete="off"
            :disable="annulling"
            required
          />
          <q-btn
            type="submit"
            class="camera-button"
            unelevated
            no-caps
            icon="block"
            label="Anular"
            :loading="annulling"
          />
          <div class="annulation-response" :class="`response-${annulationResultType}`" role="status" aria-live="polite">
            <div class="annulation-response-label">Resultado de la operación</div>
            <div>{{ annulationResult }}</div>
          </div>
        </q-form>

        <template v-else>
          <div class="camera-panel" :class="{ 'camera-active': scanning }">
            <video ref="videoElement" class="camera-video" muted playsinline></video>
            <div v-if="!scanning" class="camera-placeholder">
              <div class="scan-glyph"><q-icon name="qr_code_scanner" /></div>
              <div class="placeholder-title">{{ cameraError ? 'Cámara no disponible' : 'Lector preparado' }}</div>
              <div class="placeholder-copy">{{ cameraError || 'Activa la cámara para leer el QR de la entrada' }}</div>
            </div>
            <div v-if="scanning" class="scan-frame" aria-hidden="true"><span></span></div>
            <div class="camera-label"><span class="camera-dot" :class="{ active: scanning }"></span>{{ scanning ? 'BUSCANDO CÓDIGO' : 'CÁMARA DESACTIVADA' }}</div>
          </div>

          <div class="camera-actions">
            <q-btn
              v-if="!scanning"
              class="camera-button"
              unelevated
              no-caps
              icon="photo_camera"
              label="Activar cámara"
              :loading="startingCamera"
              @click="startCamera"
            />
            <q-btn
              v-else
              class="camera-button stop-button"
              unelevated
              no-caps
              icon="stop_circle"
              label="Detener cámara"
              @click="stopCamera"
            />
            <!--div class="camera-hint"><q-icon name="info" /> En localhost la cámara funciona sin HTTPS; desde otros dispositivos necesitarás HTTPS.</div-->
          </div>
        </template>

        <!--div class="manual-entry">
          <div class="manual-heading">
            <q-icon name="keyboard" />
            <span>Introducir código manualmente</span>
          </div>
          <q-form class="manual-form" @submit.prevent="validateCode(manualCode)">
            <q-input
              v-model="manualCode"
              outlined
              dense
              dark
              class="code-input"
              placeholder="Código de la entrada"
              autocomplete="off"
              autocapitalize="off"
              :disable="loading"
              aria-label="Código QR"
            />
            <q-btn
              type="submit"
              unelevated
              no-caps
              icon="arrow_forward"
              label="Validar"
              class="validate-button"
              :loading="loading"
              :disable="!manualCode.trim()"
            />
          </q-form>
        </div-->
      </section>

      <aside class="result-column">
        <div class="result-heading">
          <div class="eyebrow text-muted">RESULTADO</div>
          <div class="session-clock">{{ currentTime }}</div>
        </div>

        <div
          ref="resultPanel"
          class="result-panel"
          :class="`result-${result.type}`"
          aria-live="polite"
          tabindex="-1"
        >
          <div class="result-icon">
            <q-icon :name="resultIcon" />
          </div>
          <div class="result-title">{{ result.title }}</div>
          <div class="result-message">{{ result.message }}</div>
          <div v-if="result.code" class="result-code">{{ result.code }}</div>
        </div>

        <div class="shift-summary">
          <div class="summary-heading">Este dispositivo</div>
          <div class="summary-row">
            <span><span class="summary-dot entry-dot"></span>Entradas validadas</span>
            <strong>{{ totals.entrada }}</strong>
          </div>
          <div class="summary-row">
            <span><span class="summary-dot drink-dot"></span>Consumiciones</span>
            <strong>{{ totals.consumicion }}</strong>
          </div>
          <div class="summary-footnote">Contador desde que se abrió esta pantalla</div>
        </div>

        <div class="staff-note">
          <q-icon name="shield" />
          <span>Cada entrada permite un acceso y una consumición, validados por separado.</span>
        </div>
      </aside>
    </main>
    <footer class="page-footer"><span>EVENTO BENÉFICO</span><span>DIGITICKET <i>·</i> CONTROL QR</span></footer>

    <q-dialog v-model="loginDialog">
      <q-card class="auth-dialog">
        <q-form @submit.prevent="login">
          <q-card-section>
            <div class="eyebrow">ACCESO AUTORIZADO</div>
            <div class="dialog-title">Iniciar sesión</div>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <q-input
              v-model.trim="loginForm.usuario"
              outlined
              dark
              label="Usuario"
              autocomplete="username"
              :disable="loggingIn"
              required
            />
            <q-input
              v-model="loginForm.pwd"
              class="q-mt-md"
              outlined
              dark
              type="password"
              label="Contraseña"
              autocomplete="current-password"
              :disable="loggingIn"
              required
            />
            <div v-if="loginError" class="login-error" role="alert">{{ loginError }}</div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" color="grey-5" v-close-popup :disable="loggingIn" />
            <q-btn type="submit" unelevated no-caps label="Entrar" class="camera-button" :loading="loggingIn" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref } from 'vue'
import axios from 'axios'
import { BrowserMultiFormatReader } from '@zxing/browser'

defineOptions({ name: 'IndexPage' })

const mode = ref('entrada')
const isAuthenticated = ref(false)
const authorizationKey = ref('')
const loginDialog = ref(false)
const loggingIn = ref(false)
const loginError = ref('')
const loginForm = reactive({ usuario: '', pwd: '' })
const annulationForm = reactive({ autorizacion: '', accion: 'entrada', email: '', clave: '' })
const annulling = ref(false)
const annulationResult = ref('El resultado de la operación aparecerá aquí.')
const annulationResultType = ref('idle')
const manualCode = ref('')
const videoElement = ref(null)
const resultPanel = ref(null)
const scanning = ref(false)
const startingCamera = ref(false)
const loading = ref(false)
const cameraError = ref('')
const reader = new BrowserMultiFormatReader()
const cameraControls = ref(null)
const totals = reactive({ entrada: 0, consumicion: 0 })
const currentTime = ref(new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }))
const result = reactive({
  type: 'idle',
  title: 'Listo para validar',
  message: 'El resultado de cada lectura aparecerá aquí.',
  code: ''
})

const resultIcon = computed(() => ({
  idle: 'qr_code_2',
  success: 'check_circle',
  repeated: 'history',
  missing: 'person_search',
  error: 'error_outline'
}[result.type]))

const apiUrl = 'https://fundacionfuturodigital.org/php/certificado/bd_validarEntrada.php'
const modeOptions = computed(() => [
  { label: 'Entrada', value: 'entrada', icon: 'confirmation_number' },
  { label: 'Consumición', value: 'consumicion', icon: 'local_cafe' },
  ...(isAuthenticated.value ? [{ label: 'Anulación', value: 'anulacion', icon: 'block' }] : [])
])
const annulationActionOptions = [
  { label: 'Entrada', value: 'entrada' },
  { label: 'Consumición', value: 'consumicion' }
]

const isOkResponse = (data) => data === 'OK' || data?.estado === 'OK' || data?.resultado === 'OK'

const responseMessage = (data, fallback) => {
  if (typeof data === 'string') return data
  return data?.mensaje || data?.detalle || data?.estado || data?.resultado || fallback
}

const handleAuthButton = () => {
  if (isAuthenticated.value) {
    isAuthenticated.value = false
    authorizationKey.value = ''
    if (mode.value === 'anulacion') mode.value = 'entrada'
    return
  }

  loginError.value = ''
  loginDialog.value = true
}

const login = async () => {
  if (loggingIn.value) return
  loggingIn.value = true
  loginError.value = ''

  try {
    const { data } = await axios.post(apiUrl + '/validaUsuario', {
      usuario: loginForm.usuario,
      pwd: loginForm.pwd
    })

    if (!isOkResponse(data)) {
      loginError.value = responseMessage(data, 'Usuario o contraseña incorrectos.')
      return
    }

    if (data?.clave === undefined || data?.clave === null) {
      loginError.value = 'El servidor no devolvió la clave de autorización.'
      return
    }

    authorizationKey.value = String(data.clave)
    isAuthenticated.value = true
    loginDialog.value = false
    loginForm.usuario = ''
    loginForm.pwd = ''
  } catch (error) {
    loginError.value = responseMessage(
      error.response?.data,
      'No se pudo iniciar sesión. Comprueba la conexión e inténtalo de nuevo.'
    )
  } finally {
    loggingIn.value = false
  }
}

const annulEntry = async () => {
  if (annulling.value) return
  if (annulationForm.clave !== authorizationKey.value) {
    annulationResultType.value = 'error'
    annulationResult.value = 'La clave introducida no coincide con la clave de autorización. No se ha enviado la solicitud.'
    return
  }

  annulling.value = true
  annulationResultType.value = 'pending'
  annulationResult.value = 'Enviando la solicitud de anulación…'

  try {
    const { data } = await axios.post(apiUrl + '/anulaEntrada', {
      autorizacion: annulationForm.autorizacion,
      accion: annulationForm.accion,
      email: annulationForm.email,
      clave: annulationForm.clave
    })
    annulationResultType.value = data?.resultado === 'OK' ? 'success' : 'error'
    annulationResult.value = data?.detalle || 'El servidor no devolvió el detalle de la anulación.'
  } catch (error) {
    annulationResultType.value = 'error'
    annulationResult.value = responseMessage(
      error.response?.data,
      'No se pudo completar la anulación. Comprueba la conexión e inténtalo de nuevo.'
    )
  } finally {
    annulling.value = false
  }
}

const updateClock = () => {
  currentTime.value = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
}

const focusResultOnMobile = async () => {
  if (!window.matchMedia('(max-width: 800px)').matches) return

  await nextTick()
  resultPanel.value?.focus({ preventScroll: true })
  resultPanel.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

const stopCamera = () => {
  cameraControls.value?.stop()
  cameraControls.value = null
  scanning.value = false
}

const startCamera = async () => {
  if (window.matchMedia('(max-width: 800px)').matches) {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  if (!window.isSecureContext) {
    cameraError.value = 'La cámara requiere una conexión segura (HTTPS). Abre esta app desde una URL HTTPS para escanear.'
    return
  }

  if (!navigator.mediaDevices?.getUserMedia) {
    cameraError.value = 'Este navegador no permite acceder a la cámara. Prueba con una versión actualizada de Chrome o Safari.'
    return
  }

  startingCamera.value = true
  cameraError.value = ''
  try {
    const controls = await reader.decodeFromVideoDevice(undefined, videoElement.value, (decoded, error, controls) => {
      if (decoded && !loading.value) {
        stopCamera()
        validateCode(decoded.getText())
      }
      if (error) {
        const errorKind = error.getKind?.() || error.name
        if (['NotFoundException', 'ChecksumException', 'FormatException'].includes(errorKind)) return

        cameraError.value = `Se detuvo la lectura: ${error.message || errorKind || 'error desconocido'}`
        controls.stop()
        cameraControls.value = null
        scanning.value = false
      }
    })
    cameraControls.value = controls
    scanning.value = true
  } catch (error) {
    cameraError.value = error.name === 'NotAllowedError'
      ? 'Da permiso a la cámara en el navegador para continuar.'
      : 'No se pudo iniciar la cámara. Comprueba los permisos del dispositivo.'
  } finally {
    startingCamera.value = false
  }
}

const extractCode = (qrValue) => {
  const value = String(qrValue || '').trim()
  if (!value) return ''

  try {
    const parsed = JSON.parse(value)
    return parsed[0] // primer elemento del array, que es el objeto con email y autorizacion  
  } catch (error) {
      result.message = error.message
  }
}

const validateCode = async (qrValue) => {
  const code = extractCode(qrValue)
  if (!code || loading.value) return

  loading.value = true
  result.type = 'idle'
  result.title = 'Validando…'
  result.message = mode.value === 'entrada' ? 'Comprobando el acceso en la base de datos.' : 'Comprobando la consumición en la base de datos.'
  result.code = `CÓDIGO LEÍDO · ${code.email+' Aut:'+code.autorizacion}`

  try {
    const { data } = await axios.post(apiUrl+'/validaEntrada', { email: code.email, autorizacion: code.autorizacion, cantidad: code.cantidad, tipo: mode.value })
    result.type = data.estado || 'error'
    result.title = data.mensaje || 'Respuesta no reconocida'
    result.message = data.detalle || ''
    if (data.estado === 'OK') {
      totals['entrada'] = data.entradas
      totals['consumicion'] = data.consumiciones
    }
    manualCode.value = ''
    updateClock()
  } catch (error) {
    console.log(error)
    result.type = 'error'
    result.title = 'No se pudo validar'
    result.message = error.response?.data?.mensaje || 'No hay conexión con el servidor. Comprueba la red e inténtalo de nuevo.'
  } finally {
    loading.value = false
    await focusResultOnMobile()
  }
}

onBeforeUnmount(stopCamera)
</script>
