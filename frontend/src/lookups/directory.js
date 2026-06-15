export async function loadCategoryDirectory(api) {
  const payload = await api.listCategories()
  const categories = Array.isArray(payload?.data) ? payload.data : []

  return categories.map(normalizeCategory)
}

export async function loadCityDirectory(api) {
  const payload = await api.listCities()
  const cities = Array.isArray(payload?.data) ? payload.data : []

  return cities.map(normalizeCity)
}

export function normalizeCategory(category) {
  return {
    id: category.id,
    title: category.name || 'دسته بدون نام',
    slug: category.slug,
    description: category.description || 'رویدادهای این دسته در تسک فیلترها نمایش داده می شوند.',
    href: category.slug ? `/categories/${category.slug}` : '#',
    meta: category.sort_order === undefined ? null : `اولویت ${category.sort_order}`,
  }
}

export function normalizeCity(city) {
  return {
    id: city.id,
    title: city.name || 'شهر بدون نام',
    slug: city.slug,
    description: city.province ? `استان ${city.province}` : 'رویدادهای این شهر در تسک فیلترها نمایش داده می شوند.',
    href: city.slug ? `/cities/${city.slug}` : '#',
    meta: city.country_code || 'IR',
  }
}
