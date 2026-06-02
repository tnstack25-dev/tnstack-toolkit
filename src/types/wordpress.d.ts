export {}

declare global {
  interface Window {
    TNStackToolkit: {
      nonce: string
      settings_endpoint: string
    }
  }
}
