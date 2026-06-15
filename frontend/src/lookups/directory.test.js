import assert from 'node:assert/strict'
import test from 'node:test'
import {
  loadCategoryDirectory,
  loadCityDirectory,
  normalizeCategory,
  normalizeCity,
} from './directory.js'

test('loadCategoryDirectory maps active category lookup payload', async () => {
  const api = {
    listCategories: async () => ({
      data: [
        { id: 1, name: 'فناوری', slug: 'technology', description: 'Tech events', sort_order: 10 },
      ],
    }),
  }

  const categories = await loadCategoryDirectory(api)

  assert.deepEqual(categories, [
    {
      id: 1,
      title: 'فناوری',
      slug: 'technology',
      description: 'Tech events',
      href: '/categories/technology',
      meta: 'اولویت 10',
    },
  ])
})

test('loadCityDirectory maps active city lookup payload', async () => {
  const api = {
    listCities: async () => ({
      data: [
        { id: 2, name: 'تهران', slug: 'tehran', province: 'تهران', country_code: 'IR' },
      ],
    }),
  }

  const cities = await loadCityDirectory(api)

  assert.equal(cities[0].title, 'تهران')
  assert.equal(cities[0].href, '/cities/tehran')
  assert.equal(cities[0].description, 'استان تهران')
})

test('directory normalizers provide fallbacks', () => {
  assert.equal(normalizeCategory({ id: 3 }).title, 'دسته بدون نام')
  assert.equal(normalizeCategory({ id: 3 }).href, '#')
  assert.equal(normalizeCity({ id: 4 }).title, 'شهر بدون نام')
  assert.equal(normalizeCity({ id: 4 }).meta, 'IR')
})
