export function createRegistrationFormState(schema) {
  const fields = Array.isArray(schema?.fields) ? schema.fields : []

  return Object.fromEntries(fields.map((field) => [
    field.name,
    field.type === 'checkbox' ? false : '',
  ]))
}

export function buildRegistrationFormPayload(schema, state) {
  const fields = Array.isArray(schema?.fields) ? schema.fields : []
  const payload = {}

  for (const field of fields) {
    if (!field?.name) {
      continue
    }

    const value = state?.[field.name]
    if (field.type === 'checkbox') {
      payload[field.name] = Boolean(value)
      continue
    }

    if (value !== undefined && value !== null && String(value).trim() !== '') {
      payload[field.name] = String(value).trim()
    }
  }

  return payload
}
