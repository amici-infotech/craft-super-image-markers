# Twig Usage

## Basic Output

Given a field handle named `entryMapperField`:

```twig
{% set mapper = entry.entryMapperField %}
{% set image = mapper.image.one() %}

{% if image %}
    <div class="image-map">
        <img class="image-map__image" src="{{ image.url }}" alt="{{ image.title }}">

        {% for item in mapper.markers.all() %}
            <button
                class="image-map__marker"
                type="button"
                style="left: {{ item.marker.x }}%; top: {{ item.marker.y }}%;"
            >
                {{ loop.index }}
            </button>
        {% endfor %}
    </div>
{% endif %}
```

## Include Related Entry Links

```twig
{% set mapper = entry.entryMapperField %}
{% set image = mapper.image.one() %}

{% if image %}
    <div class="image-map">
        <img class="image-map__image" src="{{ image.url }}" alt="{{ image.title }}">

        {% for item in mapper.markers.all() %}
            {% set relatedEntry = item.marker.entry.one() %}

            {% if relatedEntry %}
                <a
                    class="image-map__marker"
                    href="{{ relatedEntry.url }}"
                    style="left: {{ item.marker.x }}%; top: {{ item.marker.y }}%;"
                >
                    {{ relatedEntry.title }}
                </a>
            {% else %}
                <span
                    class="image-map__marker"
                    style="left: {{ item.marker.x }}%; top: {{ item.marker.y }}%;"
                ></span>
            {% endif %}
        {% endfor %}
    </div>
{% endif %}
```

## Suggested CSS

```css
.image-map {
  position: relative;
  display: inline-block;
  max-width: 100%;
}

.image-map__image {
  display: block;
  width: 100%;
  height: auto;
}

.image-map__marker {
  position: absolute;
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid #fff;
  border-radius: 50%;
  background: #d92828;
  box-shadow: 0 2px 8px rgb(0 0 0 / 30%);
  transform: translate(-50%, -50%);
}
```

## Accessors

The field value exposes these properties:

```twig
{# Asset query #}
entry.entryMapperField.image.one()

{# Marker collection #}
entry.entryMapperField.markers.all()
entry.entryMapperField.markers.one()
entry.entryMapperField.markers.count()

{# Marker item #}
item.marker.x
item.marker.y
item.marker.entryId
item.marker.entry.one()
```

## Empty State

```twig
{% set mapper = entry.entryMapperField %}
{% set image = mapper.image.one() %}

{% if not image %}
    <p>No image map has been configured yet.</p>
{% elseif mapper.markers.count() == 0 %}
    <img src="{{ image.url }}" alt="{{ image.title }}">
{% else %}
    {# Render full image map #}
{% endif %}
```

## Working With Marker Data

Marker coordinates are numbers from `0` to `100`.

```twig
{% for item in entry.entryMapperField.markers.all() %}
    Marker {{ loop.index }}:
    X {{ item.marker.x }}%,
    Y {{ item.marker.y }}%,
    Entry ID {{ item.marker.entryId ?? 'none' }}
{% endfor %}
```

Use `left` and `top` CSS percentages with `transform: translate(-50%, -50%)` to center the marker on its coordinate.
