import assert from 'node:assert/strict'
import test from 'node:test'
import { buildRegistrationFormPayload, createRegistrationFormState } from './registration-form.js'

test('createRegistrationFormState initializes text and checkbox fields', () => {
  const state = createRegistrationFormState({
    fields: [
      { name: 'company', type: 'text' },
      { name: 'newsletter', type: 'checkbox' },
    ],
  })

  assert.deepEqual(state, {
    company: '',
    newsletter: false,
  })
})

test('buildRegistrationFormPayload trims text fields and keeps checkbox booleans', () => {
  const payload = buildRegistrationFormPayload({
    fields: [
      { name: 'company', type: 'text' },
      { name: 'newsletter', type: 'checkbox' },
    ],
  }, {
    company: ' Rokhdad ',
    newsletter: 1,
  })

  assert.deepEqual(payload, {
    company: 'Rokhdad',
    newsletter: true,
  })
})
