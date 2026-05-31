import { Button, Notice, Panel, PanelBody, Spinner, TextControl, ToggleControl } from '@wordpress/components'
import { useEffect, useState } from '@wordpress/element'

type Settings = {
  title: string
  is_enabled: boolean
}

type NoticeState = {
  status: 'success' | 'error'
  message: string
}

declare global {
  interface Window {
    TNStackToolkit: {
      nonce: string
      settings_endpoint: string
    }
  }
}

const requestSettings = async (options?: RequestInit): Promise<Settings> => {
  const response = await fetch(window.TNStackToolkit.settings_endpoint, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': window.TNStackToolkit.nonce,
      ...options?.headers,
    },
  })

  if (!response.ok) {
    throw new Error('Không thể xử lý yêu cầu cài đặt.')
  }

  return response.json()
}

function App() {
  const [settings, setSettings] = useState<Settings>({
    title: '',
    is_enabled: false,
  })
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [notice, setNotice] = useState<NoticeState | null>(null)

  useEffect(() => {
    requestSettings()
      .then(setSettings)
      .catch((error: Error) => {
        setNotice({ status: 'error', message: error.message })
      })
      .finally(() => {
        setIsLoading(false)
      })
  }, [])

  const saveSettings = async () => {
    setIsSaving(true)
    setNotice(null)

    try {
      const savedSettings = await requestSettings({
        method: 'POST',
        body: JSON.stringify(settings),
      })

      setSettings(savedSettings)
      setNotice({ status: 'success', message: 'Đã lưu cài đặt.' })
    } catch (error) {
      setNotice({
        status: 'error',
        message: error instanceof Error ? error.message : 'Không thể lưu cài đặt.',
      })
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <div className="wrap">
      <h1>TNStack Toolkit</h1>

      {notice && (
        <Notice status={notice.status} onRemove={() => setNotice(null)}>
          {notice.message}
        </Notice>
      )}

      {isLoading ? (
        <Spinner />
      ) : (
        <Panel>
          <PanelBody title="Cài đặt chung" initialOpen>
            <TextControl
              label="Tiêu đề"
              value={settings.title}
              onChange={(title) => setSettings({ ...settings, title })}
            />

            <ToggleControl
              label="Bật tính năng X"
              checked={settings.is_enabled}
              onChange={(is_enabled) => setSettings({ ...settings, is_enabled })}
            />

            <Button variant="primary" isBusy={isSaving} disabled={isSaving} onClick={saveSettings}>
              Lưu cài đặt
            </Button>
          </PanelBody>
        </Panel>
      )}
    </div>
  )
}

export default App
